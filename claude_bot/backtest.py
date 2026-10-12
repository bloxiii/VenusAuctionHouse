#!/usr/bin/env python3
"""Backtest : rejoue une stratégie sur l'historique réel et la compare au buy & hold.

Exemples :
    python backtest.py --strategy trend                  # règle simple, gratuit
    python backtest.py --strategy random                 # hasard, gratuit
    python backtest.py --strategy claude --yes           # Claude (payant, ~1 $ / an en 4h)
    python backtest.py --strategy trend --synthetic      # données fictives, hors ligne

Le résultat est enregistré dans un ledger dédié (backtest_<stratégie>.db),
consultable avec : python run_dashboard.py --db backtest_trend.db
"""

from __future__ import annotations

import argparse
import json
import logging
import sys
from pathlib import Path

from agent.config import PROJECT_ROOT, load_config
from agent.loop import Agent
from agent.metrics import from_ledger, verdict
from market.historical import INTERVAL_SECONDS, HistoricalMarket, get_history, synthetic_history
from portfolio.ledger import Ledger

COST_PER_CALL_ESTIMATE = 0.0005   # $ par appel Claude Haiku 5.5 (voir README)


def build_decider(strategy: str, cfg, anonymize: bool):
    if strategy == "trend":
        from agent.strategies import TrendDecider
        return TrendDecider()
    if strategy in ("random", "hold"):
        from agent.dummy import DummyDecider
        cfg["dry_run"]["strategy"] = strategy
        cfg["dry_run"]["simulate_api_cost"] = False
        return DummyDecider(cfg)
    from agent.llm import ClaudeDecider
    if anonymize:
        from agent.strategies import AnonymizingDecider
        aliases = [f"ACTIF_{chr(65 + i)}" for i in range(len(cfg["game"]["assets"]))]
        return AnonymizingDecider(ClaudeDecider(cfg, schema_assets=aliases), cfg["game"]["assets"])
    return ClaudeDecider(cfg)


def print_report(r: dict, strategy: str) -> None:
    pct = lambda v: f"{v:+.2f} %"  # noqa: E731
    print("\n" + "=" * 64)
    print(f" BACKTEST — stratégie : {strategy}")
    print(f" Période : {r['start'][:10]} → {r['end'][:10]}  ({r['cycles']} cycles, statut {r['status']})")
    print("=" * 64)
    print(f" {'':28}{'BOT':>14}{'BUY & HOLD':>16}")
    print(f" {'Valeur finale':28}{r['final_value']:>13.2f}${r['buy_hold_final']:>15.2f}$")
    print(f" {'Rendement':28}{pct(r['return_pct']):>14}{pct(r['buy_hold_return_pct']):>16}")
    print(f" {'Perte max depuis un sommet':28}{-r['max_drawdown_pct']:>13.2f}%{-r['buy_hold_max_drawdown_pct']:>15.2f}%")
    s, bs = r["sharpe"], r["buy_hold_sharpe"]
    print(f" {'Sharpe (rendement/risque)':28}{(f'{s:.2f}' if s is not None else '—'):>14}"
          f"{(f'{bs:.2f}' if bs is not None else '—'):>16}")
    print(f"\n Surperformance vs buy & hold : {pct(r['excess_return_pct'])}")
    print(f" Transactions : {r['trades']} · frais : {r['fees']:.2f} $ · coût API : {r['api_cost']:.4f} $")
    if r["segments"]:
        print("\n Régularité par sous-période :")
        for sg in r["segments"]:
            mark = "✔" if sg["bot_pct"] > sg["buy_hold_pct"] else "✘"
            print(f"   {mark} {sg['from'][:10]} → {sg['to'][:10]}  bot {pct(sg['bot_pct']):>10}"
                  f"  buy&hold {pct(sg['buy_hold_pct']):>10}")
    print("\n Critères avant d'envisager de l'argent réel :")
    for label, ok in verdict(r):
        print(f"   [{'✔' if ok else '✘'}] {label}")
    print("\n Rappel : un bon backtest est nécessaire mais PAS suffisant. La preuve finale")
    print(" est un paper trading en direct sur plusieurs mois, sans retoucher la stratégie.")
    print("=" * 64)


def main(argv: list[str] | None = None) -> int:
    p = argparse.ArgumentParser(description="Backtest sur l'historique (paper trading)")
    p.add_argument("--strategy", choices=["trend", "random", "hold", "claude"], default="trend")
    p.add_argument("--days", type=int, default=365)
    p.add_argument("--interval", choices=sorted(INTERVAL_SECONDS), default="4h")
    p.add_argument("--lookback", type=int, default=24, help="bougies d'historique visibles à chaque cycle")
    p.add_argument("--synthetic", action="store_true", help="historique fictif (hors ligne)")
    p.add_argument("--no-anonymize", action="store_true", help="montre à Claude les vrais noms et prix")
    p.add_argument("--yes", action="store_true", help="confirme la dépense API (stratégie claude)")
    p.add_argument("--db", help="ledger du backtest (défaut : backtest_<stratégie>.db)")
    p.add_argument("--config")
    args = p.parse_args(argv)

    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)-8s %(message)s", datefmt="%H:%M:%S")
    log = logging.getLogger("backtest")
    cfg = load_config(args.config)
    assets = cfg["game"]["assets"]

    if args.synthetic:
        history = synthetic_history(assets, args.interval, args.days)
        log.warning("Historique SYNTHÉTIQUE : résultats sans valeur pour le vrai marché (test du programme).")
    else:
        from market.binance import MarketClient
        client = MarketClient(cfg["market"], assets, cfg["game"]["quote"])
        history = get_history(client, assets, cfg["game"]["quote"], args.interval, args.days, PROJECT_ROOT / "data")

    market = HistoricalMarket(history, lookback=args.lookback)
    steps = min(market.steps, args.days * 86400 // INTERVAL_SECONDS[args.interval])
    market.index = market.length - steps   # on joue les `days` derniers jours

    # Le jeu est recalé sur l'intervalle du backtest (loyer, durée d'un cycle, nb de cycles).
    cfg["game"]["cycle_interval_seconds"] = INTERVAL_SECONDS[args.interval]
    cfg["game"]["max_cycles"] = steps
    cfg["market"]["kline_interval"] = args.interval

    if args.strategy == "claude":
        est = steps * COST_PER_CALL_ESTIMATE
        print(f"Backtest Claude : {steps} appels à {cfg['model']['name']} ≈ {est:.2f} $ de crédits API (estimation).")
        if not args.yes:
            print("Relancez avec --yes pour confirmer la dépense.")
            return 1

    try:
        decider = build_decider(args.strategy, cfg, anonymize=not args.no_anonymize)
    except Exception as exc:   # ex. clé API absente
        log.error("%s", exc)
        return 2

    db = args.db or str(PROJECT_ROOT / f"backtest_{args.strategy}.db")
    ledger = Ledger(db)
    ledger.reset()
    logging.getLogger("claude_bot").setLevel(logging.WARNING)   # pas de log par cycle
    agent = Agent(cfg, ledger, decider, market)
    state = agent.load_or_init_state()
    log.info("Backtest '%s' : %d cycles de %s…", args.strategy, steps, args.interval)
    try:
        for i in range(steps):
            state = agent.run_cycle(state)
            if state["status"] != "ALIVE":
                break
            if (i + 1) % max(1, steps // 10) == 0:
                log.info("  %3d %% — valeur %.2f $", (i + 1) * 100 // steps,
                         state["portfolio"].total_value(state["last_prices"]))
    except KeyboardInterrupt:
        log.warning("Interrompu : rapport partiel.")

    periods = 365 * 86400 / INTERVAL_SECONDS[args.interval]
    report = from_ledger(ledger, cfg["fees"]["trade_fee_rate"], periods)
    ledger.close()
    if report is None:
        log.error("Pas assez de cycles pour un rapport.")
        return 1
    report["strategy"] = args.strategy
    report["synthetic"] = args.synthetic
    report["verdict"] = verdict(report)
    Path(db).with_suffix(".json").write_text(json.dumps(report, indent=2, ensure_ascii=False))
    print_report(report, args.strategy)
    print(f"\nDétail : python run_dashboard.py --db {db}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
