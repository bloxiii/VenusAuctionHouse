"""Orchestration d'un cycle et boucle principale de l'agent.

Ordre d'un cycle :
  1. Récupérer les prix (si échec : cycle SKIPPED, aucune décision prise).
  2. Demander une décision au modèle (ou à la stratégie factice en --dry-run).
  3. Payer les coûts de survie (coût API réel + loyer) — avant de trader,
     pour que le cash ne devienne jamais négatif.
  4. Valider la réponse ; si invalide -> HOLD forcé.
  5. Appliquer les limites de risque puis exécuter l'ordre simulé.
  6. Kill switch : si valeur totale <= seuil -> DEAD, définitivement.
  7. Écrire le tout dans le ledger (transaction SQL atomique).
"""

from __future__ import annotations

import logging
import time
from datetime import datetime, timezone
from typing import Any, Protocol

from agent.config import rent_per_cycle
from agent.llm import LLMError, LLMResult
from agent.prompts import build_system_prompt, build_user_message, format_recent_decisions
from agent.validation import DecisionValidationError, parse_decision
from market.binance import MarketDataError, MarketSnapshot
from portfolio.ledger import Ledger, now_iso
from portfolio.portfolio import Trade

log = logging.getLogger("claude_bot")


class Decider(Protocol):
    def decide(self, system_prompt: str, user_message: str) -> LLMResult: ...


class Market(Protocol):
    def snapshot(self) -> MarketSnapshot: ...


def portfolio_view(state: dict[str, Any], prices: dict[str, float]) -> dict[str, Any]:
    pf = state["portfolio"]
    total = pf.total_value(prices)
    positions = {}
    for a, qty in pf.positions.items():
        value = pf.position_value(a, prices)
        basis = pf.cost_basis.get(a, 0.0)
        positions[a] = {"qty": round(qty, 10), "value_usd": round(value, 4),
                        "cost_basis_usd": round(basis, 4), "unrealized_pnl_usd": round(value - basis, 4)}
    initial = state["initial_balance"]
    return {"cash_usd": round(pf.cash, 6), "positions": positions, "total_value_usd": round(total, 6),
            "initial_balance_usd": initial, "pnl_usd": round(total - initial, 6),
            "pnl_pct": round((total / initial - 1) * 100, 4) if initial else None}


class Agent:
    def __init__(self, cfg: dict[str, Any], ledger: Ledger, decider: Decider, market: Market):
        self.cfg = cfg
        self.ledger = ledger
        self.decider = decider
        self.market = market
        self.system_prompt = build_system_prompt(cfg)
        self.fee_rate = float(cfg["fees"]["trade_fee_rate"])
        self.rent = rent_per_cycle(cfg)

    # ------------------------------------------------------------------ état
    def load_or_init_state(self) -> dict[str, Any]:
        state = self.ledger.load_state()
        if state is None:
            g = self.cfg["game"]
            state = self.ledger.init_state(g["initial_balance_usd"], g["max_cycles"], self.cfg["model"]["name"])
            log.info("Nouvel agent créé avec %.2f $ virtuels.", g["initial_balance_usd"])
        else:
            log.info("Reprise : cycle %d/%d, statut %s, valeur ~%.4f $.", state["cycle_count"],
                     state["max_cycles"], state["status"],
                     state["portfolio"].total_value(state["last_prices"]))
        state["max_cycles"] = self.cfg["game"]["max_cycles"]
        state["model"] = self.cfg["model"]["name"]
        return state

    # ----------------------------------------------------------------- cycle
    def run_cycle(self, state: dict[str, Any]) -> dict[str, Any]:
        pf = state["portfolio"]
        n = state["cycle_count"] + 1
        cycle: dict[str, Any] = {"cycle_number": n, "ts": now_iso(), "status": "OK"}
        trades: list[Trade] = []

        # 1. Données de marché --------------------------------------------
        try:
            snap = self.market.snapshot()
        except MarketDataError as exc:
            log.warning("Cycle %d SKIPPED : données de marché indisponibles (%s)", n, exc)
            cycle.update(status="SKIPPED", error=f"market: {exc}")
            prices = state.get("last_prices") or {}
            unpaid, liq = pf.pay_costs(self.rent, prices, self.fee_rate)
            trades += liq
            cycle["rent_cost"] = self.rent - unpaid
            return self._finish(state, cycle, trades, prices, unpaid)

        prices = snap.prices
        if snap.ts:                      # en backtest : heure simulée du marché
            cycle["ts"] = snap.ts
        state["last_prices"] = prices
        cycle["prices"] = prices

        # 2. Décision du modèle -------------------------------------------
        user_msg = build_user_message(
            cycle_number=n, max_cycles=state["max_cycles"],
            portfolio_view=portfolio_view(state, prices),
            market_summary=snap.summary(),
            candle_interval=self.cfg["market"].get("kline_interval", "1h"),
            recent=format_recent_decisions(self.ledger.recent_cycles(self.cfg["memory"]["recent_decisions"])),
            costs={"api_cost_total_usd": round(state["total_api_cost"], 6),
                   "rent_per_cycle_usd": round(self.rent, 6),
                   "rent_total_usd": round(state["total_rent_cost"], 6),
                   "trade_fees_total_usd": round(state["total_fees"], 6),
                   "trade_fee_rate": self.fee_rate})
        result: LLMResult | None = None
        try:
            result = self.decider.decide(self.system_prompt, user_msg)
        except LLMError as exc:
            log.error("Cycle %d : appel au modèle échoué -> HOLD forcé (%s)", n, exc)
            cycle.update(status="LLM_ERROR", error=f"llm: {exc}")

        if result is not None:
            cycle.update(raw_decision=result.text, input_tokens=result.input_tokens,
                         output_tokens=result.output_tokens, api_cost=result.cost_usd,
                         api_cost_estimated=result.estimated)

        # 3. Coûts de survie (API + loyer), payés avant de trader ---------
        costs = cycle.get("api_cost", 0.0) + self.rent
        unpaid, liq = pf.pay_costs(costs, prices, self.fee_rate)
        trades += liq
        cycle["rent_cost"] = self.rent

        # 4. Validation ---------------------------------------------------
        decision = None
        if result is not None:
            try:
                decision = parse_decision(result.text, self.cfg["game"]["assets"])
                cycle["reasoning"] = decision.reasoning
            except DecisionValidationError as exc:
                log.warning("Cycle %d : réponse invalide -> HOLD forcé (%s)", n, exc)
                cycle.update(status="INVALID", error=f"validation: {exc}",
                             reasoning="(réponse invalide, HOLD forcé)")

        # 5. Limites de risque + exécution --------------------------------
        r = self.cfg["risk"]
        if decision is not None and unpaid <= 0:
            risk = pf.apply_risk_limits(decision.action, decision.asset, decision.amount_usd, prices,
                                        self.fee_rate, r["max_trade_fraction"], r["min_trade_usd"],
                                        r["on_violation"])
            trade = pf.execute(risk, prices, self.fee_rate)
            if trade:
                trades.append(trade)
            applied = risk.as_dict()
        else:
            applied = {"action": "HOLD", "asset": None, "amount_usd": 0.0, "notes": ["HOLD forcé"]}
        cycle["applied"] = applied
        return self._finish(state, cycle, trades, prices, unpaid)

    def _finish(self, state: dict[str, Any], cycle: dict[str, Any], trades: list[Trade],
                prices: dict[str, float], unpaid: float) -> dict[str, Any]:
        pf = state["portfolio"]
        state["cycle_count"] = cycle["cycle_number"]
        state["total_api_cost"] += cycle.get("api_cost", 0.0)
        state["total_rent_cost"] += cycle.get("rent_cost", 0.0)
        cycle["fees"] = sum(t.fee for t in trades)
        state["total_fees"] += cycle["fees"]
        value = pf.total_value(prices)
        cycle["portfolio_value"] = value

        # 6. Kill switch ---------------------------------------------------
        threshold = float(self.cfg["survival"]["kill_threshold_usd"])
        if unpaid > 0 or pf.is_dead(prices, threshold):
            state.update(status="DEAD", ended_at=now_iso(),
                         cause=f"Valeur du portefeuille tombée à {value:.6f} $ au cycle {cycle['cycle_number']}"
                               + (f" (coûts impayés : {unpaid:.6f} $)" if unpaid > 0 else ""))
            cycle["status"] = "DEAD"
            log.critical("KILL SWITCH : %s", state["cause"])
        elif state["cycle_count"] >= state["max_cycles"]:
            state.update(status="COMPLETED", ended_at=now_iso(),
                         cause=f"A survécu aux {state['max_cycles']} cycles")
            log.info("Partie terminée : %s", state["cause"])

        self.ledger.commit_cycle(state, cycle, trades)   # 7. écriture atomique
        a = cycle.get("applied") or {}
        log.info("Cycle %d/%d [%s] %s %s %.2f$ | valeur %.4f $ | coût API %.6f $%s",
                 cycle["cycle_number"], state["max_cycles"], cycle["status"], a.get("action", "-"),
                 a.get("asset") or "", a.get("amount_usd", 0.0), value, cycle.get("api_cost", 0.0),
                 " (estimé)" if cycle.get("api_cost_estimated") else "")
        return state

    # ---------------------------------------------------------------- boucle
    def run(self, interval_seconds: float) -> dict[str, Any]:
        state = self.load_or_init_state()
        if state["status"] != "ALIVE":
            log.warning("L'agent n'est plus en vie (%s : %s). Utilisez --reset pour recommencer.",
                        state["status"], state.get("cause"))
            return state

        # Reprise : on respecte l'intervalle depuis le dernier cycle enregistré.
        last = self.ledger.recent_cycles(1)
        if last:
            elapsed = (datetime.now(timezone.utc) - datetime.fromisoformat(last[0]["ts"])).total_seconds()
            wait = max(0.0, interval_seconds - elapsed)
            if wait > 0:
                log.info("Prochain cycle dans %.0f s.", wait)
                time.sleep(wait)

        while state["status"] == "ALIVE" and state["cycle_count"] < state["max_cycles"]:
            started = time.monotonic()
            state = self.run_cycle(state)
            if state["status"] != "ALIVE":
                break
            time.sleep(max(0.0, interval_seconds - (time.monotonic() - started)))
        return state
