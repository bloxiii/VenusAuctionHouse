"""Ledger SQLite : historique des cycles, transactions et état de l'agent.

Chaque cycle est écrit dans UNE transaction SQL (ligne de cycle + trades + état).
Si le programme est tué en plein cycle, rien n'est à moitié écrit : au
redémarrage on reprend depuis le dernier cycle validé.
"""

from __future__ import annotations

import json
import sqlite3
from datetime import datetime, timezone
from typing import Any

from portfolio.portfolio import Portfolio, Trade

SCHEMA = """
CREATE TABLE IF NOT EXISTS agent_state (
    id               INTEGER PRIMARY KEY CHECK (id = 1),
    status           TEXT NOT NULL,          -- ALIVE | DEAD | COMPLETED
    cause            TEXT,                   -- cause de la mort / de l'arrêt
    started_at       TEXT NOT NULL,
    ended_at         TEXT,
    cycle_count      INTEGER NOT NULL DEFAULT 0,
    max_cycles       INTEGER NOT NULL,
    initial_balance  REAL NOT NULL,
    portfolio_json   TEXT NOT NULL,
    last_prices_json TEXT,
    total_api_cost   REAL NOT NULL DEFAULT 0,
    total_rent_cost  REAL NOT NULL DEFAULT 0,
    total_fees       REAL NOT NULL DEFAULT 0,
    model            TEXT,
    updated_at       TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS cycles (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    cycle_number      INTEGER NOT NULL,
    ts                TEXT NOT NULL,
    status            TEXT NOT NULL,         -- OK | SKIPPED | INVALID | LLM_ERROR | DEAD
    prices_json       TEXT,
    raw_decision      TEXT,                  -- texte brut renvoyé par le modèle
    applied_json      TEXT,                  -- décision réellement appliquée
    reasoning         TEXT,
    error             TEXT,
    input_tokens      INTEGER NOT NULL DEFAULT 0,
    output_tokens     INTEGER NOT NULL DEFAULT 0,
    api_cost          REAL NOT NULL DEFAULT 0,
    api_cost_estimated INTEGER NOT NULL DEFAULT 0,
    rent_cost         REAL NOT NULL DEFAULT 0,
    fees              REAL NOT NULL DEFAULT 0,
    cash              REAL NOT NULL,
    positions_json    TEXT NOT NULL,
    portfolio_value   REAL NOT NULL
);
CREATE TABLE IF NOT EXISTS transactions (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    cycle_number  INTEGER NOT NULL,
    ts            TEXT NOT NULL,
    side          TEXT NOT NULL,
    asset         TEXT NOT NULL,
    qty           REAL NOT NULL,
    price         REAL NOT NULL,
    notional      REAL NOT NULL,
    fee           REAL NOT NULL,
    reason        TEXT NOT NULL,
    cash_after    REAL NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_cycles_number ON cycles(cycle_number);
"""


def now_iso() -> str:
    return datetime.now(timezone.utc).isoformat(timespec="seconds")


class Ledger:
    def __init__(self, path: str, readonly: bool = False):
        self.path = path
        if readonly:
            self.conn = sqlite3.connect(f"file:{path}?mode=ro", uri=True, check_same_thread=False)
        else:
            self.conn = sqlite3.connect(path, check_same_thread=False)
            self.conn.execute("PRAGMA journal_mode=WAL")   # lecture concurrente par le dashboard
            self.conn.executescript(SCHEMA)
        self.conn.row_factory = sqlite3.Row

    def close(self) -> None:
        self.conn.close()

    # ------------------------------------------------------------------ état
    def load_state(self) -> dict[str, Any] | None:
        row = self.conn.execute("SELECT * FROM agent_state WHERE id = 1").fetchone()
        if row is None:
            return None
        state = dict(row)
        state["portfolio"] = Portfolio.from_dict(json.loads(state.pop("portfolio_json")))
        lp = state.pop("last_prices_json")
        state["last_prices"] = json.loads(lp) if lp else {}
        return state

    def init_state(self, initial_balance: float, max_cycles: int, model: str) -> dict[str, Any]:
        ts = now_iso()
        pf = Portfolio(cash=initial_balance)
        with self.conn:
            self.conn.execute(
                "INSERT INTO agent_state (id, status, started_at, max_cycles, initial_balance,"
                " portfolio_json, model, updated_at) VALUES (1, 'ALIVE', ?, ?, ?, ?, ?, ?)",
                (ts, max_cycles, initial_balance, json.dumps(pf.to_dict()), model, ts))
        return self.load_state()  # type: ignore[return-value]

    def reset(self) -> None:
        with self.conn:
            for t in ("agent_state", "cycles", "transactions"):
                self.conn.execute(f"DELETE FROM {t}")

    # --------------------------------------------------------------- écriture
    def commit_cycle(self, state: dict[str, Any], cycle: dict[str, Any], trades: list[Trade]) -> None:
        """Écrit atomiquement un cycle, ses transactions et le nouvel état."""
        pf: Portfolio = state["portfolio"]
        ts = cycle.get("ts") or now_iso()
        with self.conn:
            self.conn.execute(
                "INSERT INTO cycles (cycle_number, ts, status, prices_json, raw_decision, applied_json,"
                " reasoning, error, input_tokens, output_tokens, api_cost, api_cost_estimated,"
                " rent_cost, fees, cash, positions_json, portfolio_value)"
                " VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                (cycle["cycle_number"], ts, cycle["status"],
                 json.dumps(cycle.get("prices")) if cycle.get("prices") else None,
                 cycle.get("raw_decision"),
                 json.dumps(cycle.get("applied")) if cycle.get("applied") else None,
                 cycle.get("reasoning"), cycle.get("error"),
                 cycle.get("input_tokens", 0), cycle.get("output_tokens", 0),
                 cycle.get("api_cost", 0.0), int(cycle.get("api_cost_estimated", False)),
                 cycle.get("rent_cost", 0.0), cycle.get("fees", 0.0),
                 pf.cash, json.dumps(pf.positions), cycle["portfolio_value"]))
            for t in trades:
                self.conn.execute(
                    "INSERT INTO transactions (cycle_number, ts, side, asset, qty, price, notional,"
                    " fee, reason, cash_after) VALUES (?,?,?,?,?,?,?,?,?,?)",
                    (cycle["cycle_number"], ts, t.side, t.asset, t.qty, t.price, t.notional,
                     t.fee, t.reason, pf.cash))
            self.conn.execute(
                "UPDATE agent_state SET status=?, cause=?, ended_at=?, cycle_count=?, portfolio_json=?,"
                " last_prices_json=?, total_api_cost=?, total_rent_cost=?, total_fees=?, model=?,"
                " updated_at=? WHERE id = 1",
                (state["status"], state.get("cause"), state.get("ended_at"), state["cycle_count"],
                 json.dumps(pf.to_dict()), json.dumps(state.get("last_prices") or {}),
                 state["total_api_cost"], state["total_rent_cost"], state["total_fees"],
                 state.get("model"), now_iso()))

    def mark_status(self, status: str, cause: str) -> None:
        with self.conn:
            self.conn.execute("UPDATE agent_state SET status=?, cause=?, ended_at=?, updated_at=? WHERE id=1",
                              (status, cause, now_iso(), now_iso()))

    # ---------------------------------------------------------------- lecture
    def recent_cycles(self, limit: int) -> list[dict[str, Any]]:
        rows = self.conn.execute("SELECT * FROM cycles ORDER BY id DESC LIMIT ?", (limit,)).fetchall()
        return [dict(r) for r in rows]

    def value_history(self) -> list[dict[str, Any]]:
        rows = self.conn.execute("SELECT cycle_number, ts, portfolio_value FROM cycles ORDER BY id").fetchall()
        return [dict(r) for r in rows]

    def recent_transactions(self, limit: int) -> list[dict[str, Any]]:
        rows = self.conn.execute("SELECT * FROM transactions ORDER BY id DESC LIMIT ?", (limit,)).fetchall()
        return [dict(r) for r in rows]
