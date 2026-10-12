"""Indicateurs de performance : le bot fait-il mieux que « acheter et garder » ?"""

from __future__ import annotations

import json
import math
from typing import Any


def buy_and_hold_series(price_rows: list[dict[str, float]], initial: float, fee_rate: float) -> list[float]:
    """Valeur d'un portefeuille réparti à parts égales sur les actifs au 1er cycle, jamais touché.

    Les frais d'achat initiaux sont déduits, pour comparer à armes égales.
    """
    if not price_rows:
        return []
    first = price_rows[0]
    assets = list(first)
    invested = initial * (1 - fee_rate)
    return [invested / len(assets) * sum(row[a] / first[a] for a in assets) for row in price_rows]


def max_drawdown(values: list[float]) -> float:
    """Pire baisse depuis un plus haut, en fraction (0.25 = -25 %)."""
    peak, worst = -math.inf, 0.0
    for v in values:
        peak = max(peak, v)
        if peak > 0:
            worst = max(worst, 1 - v / peak)
    return worst


def sharpe(values: list[float], periods_per_year: float) -> float | None:
    """Ratio de Sharpe annualisé (rendement / risque, taux sans risque = 0)."""
    rets = [values[i] / values[i - 1] - 1 for i in range(1, len(values)) if values[i - 1] > 0]
    if len(rets) < 2:
        return None
    mean = sum(rets) / len(rets)
    sd = math.sqrt(sum((r - mean) ** 2 for r in rets) / (len(rets) - 1))
    return mean / sd * math.sqrt(periods_per_year) if sd > 0 else None


def summarize(values: list[float], bench: list[float], initial: float, periods_per_year: float) -> dict[str, Any]:
    final, bench_final = values[-1], bench[-1]
    return {
        "final_value": final,
        "return_pct": (final / initial - 1) * 100,
        "buy_hold_final": bench_final,
        "buy_hold_return_pct": (bench_final / initial - 1) * 100,
        "excess_return_pct": (final - bench_final) / initial * 100,
        "max_drawdown_pct": max_drawdown([initial] + values) * 100,
        "buy_hold_max_drawdown_pct": max_drawdown([initial] + bench) * 100,
        "sharpe": sharpe([initial] + values, periods_per_year),
        "buy_hold_sharpe": sharpe([initial] + bench, periods_per_year),
    }


def from_ledger(ledger, fee_rate: float, periods_per_year: float, segments: int = 4) -> dict[str, Any] | None:
    """Calcule le rapport complet à partir des cycles enregistrés."""
    rows = list(ledger.conn.execute(
        "SELECT cycle_number, ts, prices_json, portfolio_value FROM cycles WHERE prices_json IS NOT NULL ORDER BY id"))
    if len(rows) < 2:
        return None
    state = ledger.load_state()
    initial = state["initial_balance"]
    prices = [json.loads(r["prices_json"]) for r in rows]
    values = [r["portfolio_value"] for r in rows]
    bench = buy_and_hold_series(prices, initial, fee_rate)
    report = summarize(values, bench, initial, periods_per_year)
    report.update({
        "cycles": len(rows), "start": rows[0]["ts"], "end": rows[-1]["ts"],
        "status": state["status"], "trades": ledger.conn.execute(
            "SELECT COUNT(*) FROM transactions WHERE reason='AGENT'").fetchone()[0],
        "fees": state["total_fees"], "api_cost": state["total_api_cost"],
    })
    # Régularité : le bot bat-il le buy & hold sur chaque sous-période, pas juste au total ?
    seg = []
    size = len(values) // segments
    for k in range(segments if size >= 2 else 0):
        lo, hi = k * size, (k + 1) * size if k < segments - 1 else len(values)
        v0 = values[lo - 1] if lo else initial
        b0 = bench[lo - 1] if lo else initial
        seg.append({"from": rows[lo]["ts"], "to": rows[hi - 1]["ts"],
                    "bot_pct": (values[hi - 1] / v0 - 1) * 100, "buy_hold_pct": (bench[hi - 1] / b0 - 1) * 100})
    report["segments"] = seg
    return report


# Critères minimaux avant d'envisager de l'argent réel. Volontairement exigeants.
CRITERIA = [
    ("Bat le buy & hold sur la période (frais et coûts API inclus)", lambda r: r["excess_return_pct"] > 0),
    ("Ratio de Sharpe supérieur au buy & hold", lambda r: (r["sharpe"] or -9) > (r["buy_hold_sharpe"] or -9)),
    ("Perte maximale depuis un sommet ≤ 25 %", lambda r: r["max_drawdown_pct"] <= 25),
    ("Bat le buy & hold sur au moins 3 sous-périodes sur 4",
     lambda r: sum(s["bot_pct"] > s["buy_hold_pct"] for s in r["segments"]) >= 3),
    ("Au moins 30 transactions (sinon résultat dû au hasard)", lambda r: r["trades"] >= 30),
]


def verdict(report: dict[str, Any]) -> list[tuple[str, bool]]:
    return [(label, bool(test(report))) for label, test in CRITERIA]
