"""Backtest : pas de lecture du futur, indicateurs, stratégie de référence, anonymisation."""

import json

import pytest

from agent.llm import LLMResult
from agent.loop import Agent
from agent.metrics import buy_and_hold_series, from_ledger, max_drawdown, sharpe, verdict
from agent.strategies import AnonymizingDecider, TrendDecider, extract_payload
from market.binance import Candle
from market.historical import HistoricalMarket, synthetic_history
from portfolio.ledger import Ledger


def candles(closes):
    return [Candle(i * 3_600_000, c, c, c, c, 0) for i, c in enumerate(closes)]


# ------------------------------------------------------------ pas de futur
def test_historical_market_never_shows_future():
    hist = {"BTC": candles(range(1, 41)), "ETH": candles(range(101, 141))}
    m = HistoricalMarket(hist, lookback=10)
    for _ in range(m.steps):
        i = m.index
        snap = m.snapshot()
        assert snap.prices["BTC"] == i + 1                      # prix = clôture de la bougie courante
        assert max(c.close for c in snap.history["BTC"]) == i + 1  # rien après elle
        assert len(snap.history["BTC"]) == 10
    with pytest.raises(Exception):
        m.snapshot()


def test_synthetic_history_is_reproducible():
    a = synthetic_history(["BTC", "ETH"], "4h", 30, seed=1)
    b = synthetic_history(["BTC", "ETH"], "4h", 30, seed=1)
    assert [c.close for c in a["BTC"]] == [c.close for c in b["BTC"]]


# ---------------------------------------------------------------- métriques
def test_buy_and_hold_equal_weight_with_fee():
    rows = [{"A": 10, "B": 100}, {"A": 20, "B": 100}]
    assert buy_and_hold_series(rows, 100, 0.0) == pytest.approx([100, 150])
    assert buy_and_hold_series(rows, 100, 0.001)[0] == pytest.approx(99.9)


def test_max_drawdown():
    assert max_drawdown([100, 120, 60, 130]) == pytest.approx(0.5)
    assert max_drawdown([1, 2, 3]) == 0


def test_sharpe_sign():
    assert sharpe([100, 101, 102.5, 103, 104.2], 365) > 0
    assert sharpe([100, 100], 365) is None


# ------------------------------------------------------- stratégie de référence
def msg(closes_by_asset, positions=None, total=50.0):
    payload = {"portfolio": {"total_value_usd": total, "positions": positions or {}},
               "market": {a: {"price": c[-1], "closes": c} for a, c in closes_by_asset.items()},
               "recent_decisions": []}
    return "x\n<donnees>\n" + json.dumps(payload) + "\n</donnees>"


def test_trend_buys_uptrend_and_sells_downtrend():
    up, down = list(range(100, 124)), list(range(124, 100, -1))
    d = json.loads(TrendDecider().decide("", msg({"BTC": up, "ETH": down})).text)
    assert (d["action"], d["asset"]) == ("BUY", "BTC")
    d = json.loads(TrendDecider().decide("", msg({"BTC": down}, {"BTC": {"value_usd": 20}})).text)
    assert (d["action"], d["asset"], d["amount_usd"]) == ("SELL", "BTC", 20)


# --------------------------------------------------------------- anonymisation
class Spy:
    def __init__(self, answer):
        self.answer = answer

    def decide(self, system_prompt, user_message):
        self.system, self.user = system_prompt, user_message
        return LLMResult(text=self.answer)


def test_anonymizer_hides_names_and_prices_and_maps_back():
    spy = Spy('{"action":"BUY","asset":"ACTIF_B","amount_usd":5,"reasoning":"r"}')
    anon = AnonymizingDecider(spy, ["BTC", "ETH", "SOL"])
    user = msg({"BTC": [65000.0, 66000.0], "ETH": [3200.0, 3300.0]},
               {"BTC": {"qty": 0.0003, "value_usd": 19.8}})
    res = anon.decide('Actifs : BTC, ETH, SOL ; "BTC" | "ETH" | "SOL"', user)
    seen = spy.system + spy.user
    for real in ("BTC", "ETH", "SOL", "65000", "66000", "3200", "0.0003"):
        assert real not in seen
    data = extract_payload(spy.user)
    assert data["market"]["ACTIF_A"]["price"] == pytest.approx(100.0)   # indice base 100
    assert json.loads(res.text)["asset"] == "ETH"                        # retraduit


# ------------------------------------------------------- backtest de bout en bout
def test_end_to_end_backtest_report(cfg):
    hist = synthetic_history(cfg["game"]["assets"], "4h", 60, seed=3)
    market = HistoricalMarket(hist, lookback=24)
    cfg["game"]["max_cycles"] = market.steps
    ledger = Ledger(cfg["storage"]["db_path"])
    agent = Agent(cfg, ledger, TrendDecider(), market)
    state = agent.load_or_init_state()
    for _ in range(market.steps):
        state = agent.run_cycle(state)
    assert state["status"] == "COMPLETED"
    r = from_ledger(ledger, 0.001, 365 * 6)
    assert r["cycles"] == market.steps and len(r["segments"]) == 4
    assert r["start"].startswith("2025-01")                              # horodatage simulé, pas « maintenant »
    assert len(verdict(r)) == 5
