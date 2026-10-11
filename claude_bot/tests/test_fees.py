"""Calcul des frais de transaction et du coût API."""

import pytest

from agent.config import rent_per_cycle
from agent.llm import compute_cost
from portfolio.portfolio import Portfolio

FEE = 0.001


def test_buy_fee_is_point_one_percent():
    pf = Portfolio(cash=50.0)
    t = pf.buy("BTC", 20.0, 50_000.0, FEE)
    assert t.fee == pytest.approx(0.02)
    assert t.qty == pytest.approx(20.0 / 50_000.0)
    assert pf.cash == pytest.approx(50.0 - 20.0 - 0.02)
    assert pf.cost_basis["BTC"] == pytest.approx(20.02)


def test_sell_fee_deducted_from_proceeds():
    pf = Portfolio(cash=0.0, positions={"ETH": 0.01}, cost_basis={"ETH": 25.0})
    t = pf.sell("ETH", 10.0, 2_000.0, FEE)
    assert t.fee == pytest.approx(0.01)
    assert pf.cash == pytest.approx(9.99)
    assert pf.positions["ETH"] == pytest.approx(0.005)
    assert pf.cost_basis["ETH"] == pytest.approx(12.5)   # coût de revient au prorata


def test_round_trip_loses_two_fees():
    pf = Portfolio(cash=50.0)
    pf.buy("SOL", 25.0, 100.0, FEE)
    pf.sell("SOL", pf.position_value("SOL", {"SOL": 100.0}), 100.0, FEE)
    assert pf.cash == pytest.approx(50.0 - 0.025 - 0.025)
    assert "SOL" not in pf.positions


def test_api_cost_from_tokens():
    pricing = {"input_per_mtok": 0.10, "output_per_mtok": 0.50,
               "cache_write_per_mtok": 0.125, "cache_read_per_mtok": 0.01}
    assert compute_cost(pricing, 1_000_000, 0) == pytest.approx(0.10)
    assert compute_cost(pricing, 2_000, 400) == pytest.approx(0.0002 + 0.0002)
    assert compute_cost(pricing, 0, 0, 1_000_000, 1_000_000) == pytest.approx(0.135)


def test_rent_prorated_per_cycle(cfg):
    cfg["survival"]["rent_per_month_usd"] = 30.0
    cfg["game"]["cycle_interval_seconds"] = 600
    # 30 $/mois = 1 $/jour = 1/144 $ par cycle de 10 min
    assert rent_per_cycle(cfg) == pytest.approx(1 / 144)
