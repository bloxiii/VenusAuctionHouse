"""Stratégies de référence (sans IA) et anonymisation pour le backtest.

- `TrendDecider` : règle simple de suivi de tendance. Si Claude ne fait pas
  mieux qu'elle, payer l'IA n'apporte rien.
- `AnonymizingDecider` : enveloppe un décideur (Claude) en masquant le nom des
  actifs et le niveau réel des prix, pour qu'il ne puisse pas « reconnaître »
  une période historique vue pendant son entraînement.
"""

from __future__ import annotations

import json
import re
from typing import Any

from agent.llm import LLMResult

_DATA = re.compile(r"<donnees>\n(.*)\n</donnees>", re.DOTALL)


def extract_payload(user_message: str) -> dict[str, Any]:
    m = _DATA.search(user_message)
    if not m:
        raise ValueError("message sans bloc <donnees>")
    return json.loads(m.group(1))


def _hold(reason: str) -> str:
    return json.dumps({"action": "HOLD", "asset": None, "amount_usd": 0, "reasoning": reason}, ensure_ascii=False)


class TrendDecider:
    """Moyenne courte > moyenne longue (+ marge) => achat ; sinon vente."""

    def __init__(self, short: int = 6, margin: float = 0.005):
        self.short = short
        self.margin = margin

    def decide(self, system_prompt: str, user_message: str) -> LLMResult:
        data = extract_payload(user_message)
        pf = data["portfolio"]
        total = pf["total_value_usd"]
        n_assets = len(data["market"])
        # 1) Ventes en priorité : tendance cassée sur un actif détenu.
        for asset, m in data["market"].items():
            closes = m.get("closes") or []
            if len(closes) < self.short + 2:
                continue
            short = sum(closes[-self.short:]) / self.short
            long = sum(closes) / len(closes)
            held = pf["positions"].get(asset, {}).get("value_usd", 0.0)
            if held > 1 and short < long:
                return LLMResult(text=json.dumps({"action": "SELL", "asset": asset, "amount_usd": held,
                                                  "reasoning": f"Tendance baissière sur {asset}"}))
        # 2) Achats : tendance haussière sur un actif non détenu.
        for asset, m in data["market"].items():
            closes = m.get("closes") or []
            if len(closes) < self.short + 2:
                continue
            short = sum(closes[-self.short:]) / self.short
            long = sum(closes) / len(closes)
            held = pf["positions"].get(asset, {}).get("value_usd", 0.0)
            if held < 1 and short > long * (1 + self.margin):
                return LLMResult(text=json.dumps({"action": "BUY", "asset": asset,
                                                  "amount_usd": round(total / n_assets, 2),
                                                  "reasoning": f"Tendance haussière sur {asset}"}))
        return LLMResult(text=_hold("Pas de signal"))


class AnonymizingDecider:
    def __init__(self, inner, assets: list[str]):
        self.inner = inner
        self.to_alias = {a: f"ACTIF_{chr(65 + i)}" for i, a in enumerate(assets)}
        self.from_alias = {v: k for k, v in self.to_alias.items()}
        self.scale: dict[str, float] = {}   # facteur fixé à la 1re observation : prix -> base 100

    @property
    def aliases(self) -> list[str]:
        return list(self.from_alias)

    def _system(self, text: str) -> str:
        for real, alias in self.to_alias.items():
            text = text.replace(f'"{real}"', f'"{alias}"').replace(real, alias)
        return text + ("\n\nNOTE : les actifs sont anonymisés et leurs prix exprimés en indice "
                       "(base 100 au début de la partie). Les montants en $ du portefeuille sont réels.")

    def _payload(self, data: dict[str, Any]) -> dict[str, Any]:
        market = {}
        for asset, m in data["market"].items():
            k = self.scale.setdefault(asset, 100.0 / m["price"])
            mm = dict(m)
            for f in ("price", "high_window", "low_window"):
                if f in mm:
                    mm[f] = round(mm[f] * k, 4)
            if "closes" in mm:
                mm["closes"] = [round(c * k, 4) for c in mm["closes"]]
            market[self.to_alias[asset]] = mm
        pf = dict(data["portfolio"])
        # La quantité détenue trahirait le niveau de prix réel : on la retire.
        pf["positions"] = {self.to_alias[a]: {k: v for k, v in p.items() if k != "qty"}
                           for a, p in pf["positions"].items()}
        recent = []
        for r in data["recent_decisions"]:
            r = dict(r)
            if r.get("applied") and r["applied"].get("asset"):
                r["applied"] = dict(r["applied"], asset=self.to_alias.get(r["applied"]["asset"]))
            recent.append(r)
        return dict(data, market=market, portfolio=pf, recent_decisions=recent)

    def decide(self, system_prompt: str, user_message: str) -> LLMResult:
        data = self._payload(extract_payload(user_message))
        msg = _DATA.sub(lambda _: "<donnees>\n" + json.dumps(data, ensure_ascii=False, separators=(",", ":"))
                        + "\n</donnees>", user_message)
        result = self.inner.decide(self._system(system_prompt), msg)
        # Retraduction de l'alias vers le vrai symbole (la validation se fait ensuite).
        try:
            obj = json.loads(result.text)
            if isinstance(obj, dict) and obj.get("asset") in self.from_alias:
                obj["asset"] = self.from_alias[obj["asset"]]
                result.text = json.dumps(obj, ensure_ascii=False)
        except (TypeError, ValueError):
            pass   # texte invalide : la validation forcera un HOLD
        return result
