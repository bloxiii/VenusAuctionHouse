"""Tableau de bord local (Flask) : http://localhost:8000

Lecture seule : le dashboard ne modifie jamais le ledger.
"""

from __future__ import annotations

import json
import os
from typing import Any

from flask import Flask, jsonify, render_template

from agent.metrics import buy_and_hold_series, max_drawdown
from portfolio.ledger import Ledger


def build_payload(db_path: str, fee_rate: float = 0.001) -> dict[str, Any]:
    if not os.path.exists(db_path):
        return {"ready": False, "message": "Aucun ledger trouvé : lancez d'abord run_agent.py"}
    ledger = Ledger(db_path, readonly=True)
    try:
        state = ledger.load_state()
        if state is None:
            return {"ready": False, "message": "Agent pas encore initialisé"}
        pf = state["portfolio"]
        prices = state["last_prices"]
        value = pf.total_value(prices)
        initial = state["initial_balance"]
        positions = []
        for a, qty in sorted(pf.positions.items()):
            v = pf.position_value(a, prices)
            basis = pf.cost_basis.get(a, 0.0)
            positions.append({"asset": a, "qty": qty, "price": prices.get(a), "value": v,
                              "cost_basis": basis, "pnl": v - basis})
        decisions = []
        for c in ledger.recent_cycles(25):
            applied = json.loads(c["applied_json"]) if c["applied_json"] else None
            decisions.append({
                "cycle": c["cycle_number"], "ts": c["ts"], "status": c["status"],
                "applied": applied, "reasoning": c["reasoning"], "error": c["error"],
                "api_cost": c["api_cost"], "estimated": bool(c["api_cost_estimated"]),
                "tokens": c["input_tokens"] + c["output_tokens"], "value": c["portfolio_value"],
            })
        # Référence : buy & hold à parts égales depuis le 1er cycle avec des prix.
        rows = [r for r in ledger.value_history_with_prices() if r["prices_json"]]
        bench = buy_and_hold_series([json.loads(r["prices_json"]) for r in rows], initial, fee_rate)
        bench_by_cycle = {r["cycle_number"]: b for r, b in zip(rows, bench)}
        history = [{"cycle": 0, "value": initial, "bench": initial}]
        for h in ledger.value_history():
            history.append({"cycle": h["cycle_number"], "value": h["portfolio_value"],
                            "bench": bench_by_cycle.get(h["cycle_number"])})
        bench_value = bench[-1] if bench else initial
        return {
            "ready": True,
            "bench_value": bench_value,
            "bench_pct": (bench_value / initial - 1) * 100 if initial else 0,
            "excess_pct": (value - bench_value) / initial * 100 if initial else 0,
            "drawdown_pct": max_drawdown([h["value"] for h in history]) * 100,
            "status": state["status"], "cause": state["cause"], "model": state["model"],
            "started_at": state["started_at"], "updated_at": state["updated_at"],
            "cycle": state["cycle_count"], "max_cycles": state["max_cycles"],
            "initial": initial, "value": value, "cash": pf.cash,
            "pnl": value - initial, "pnl_pct": (value / initial - 1) * 100 if initial else 0,
            "api_cost": state["total_api_cost"], "rent_cost": state["total_rent_cost"],
            "fees": state["total_fees"], "prices": prices, "positions": positions,
            "history": history,
            "decisions": decisions,
            "transactions": ledger.recent_transactions(15),
        }
    finally:
        ledger.close()


def create_app(cfg: dict[str, Any]) -> Flask:
    app = Flask(__name__)
    db_path = cfg["storage"]["db_path"]

    @app.get("/")
    def index():
        return render_template("index.html", refresh_ms=int(cfg["dashboard"]["refresh_seconds"] * 1000))

    @app.get("/api/state")
    def api_state():
        return jsonify(build_payload(db_path, cfg["fees"]["trade_fee_rate"]))

    return app
