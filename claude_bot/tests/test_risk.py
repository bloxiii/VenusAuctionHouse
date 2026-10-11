"""Limites de risque appliquées par le code."""

import pytest

from portfolio.portfolio import Portfolio

FEE = 0.001
PRICES = {"BTC": 50_000.0, "ETH": 2_500.0, "SOL": 100.0}


def limits(pf, action, asset, amount, mode="clamp"):
    return pf.apply_risk_limits(action, asset, amount, PRICES, FEE, 0.5, 1.0, mode)


def test_trade_capped_at_half_portfolio():
    r = limits(Portfolio(cash=50.0), "BUY", "BTC", 40.0)
    assert r.action == "BUY" and r.amount_usd == pytest.approx(25.0)
    assert r.notes


def test_reject_mode_forces_hold():
    r = limits(Portfolio(cash=50.0), "BUY", "BTC", 40.0, mode="reject")
    assert r.action == "HOLD"


def test_no_leverage_buy_limited_to_cash_including_fees():
    # Portefeuille de 100 $ dont seulement 10 $ de cash : plafond 50 $ mais cash 10 $.
    pf = Portfolio(cash=10.0, positions={"SOL": 0.9})
    r = limits(pf, "BUY", "ETH", 30.0)
    assert r.amount_usd == pytest.approx(10.0 / 1.001)
    pf.execute(r, PRICES, FEE)
    assert pf.cash >= 0
    assert pf.cash == pytest.approx(0.0, abs=1e-9)


def test_no_short_selling():
    pf = Portfolio(cash=50.0)
    r = limits(pf, "SELL", "BTC", 10.0)
    assert r.action == "HOLD"           # rien à vendre (0 $ < minimum)


def test_sell_limited_to_position():
    pf = Portfolio(cash=40.0, positions={"SOL": 0.05})   # 5 $ de SOL
    r = limits(pf, "SELL", "SOL", 20.0)
    assert r.action == "SELL" and r.amount_usd == pytest.approx(5.0)
    pf.execute(r, PRICES, FEE)
    assert "SOL" not in pf.positions


def test_sell_direct_short_raises():
    pf = Portfolio(cash=10.0, positions={"SOL": 0.01})
    with pytest.raises(ValueError):
        pf.sell("SOL", 50.0, 100.0, FEE)


def test_buy_without_cash_raises():
    with pytest.raises(ValueError):
        Portfolio(cash=5.0).buy("BTC", 10.0, 50_000.0, FEE)


def test_below_minimum_is_hold():
    r = limits(Portfolio(cash=50.0), "BUY", "SOL", 0.5)
    assert r.action == "HOLD"


def test_unknown_price_is_hold():
    pf = Portfolio(cash=50.0)
    r = pf.apply_risk_limits("BUY", "DOGE", 5.0, PRICES, FEE, 0.5, 1.0)
    assert r.action == "HOLD"


def test_pay_costs_liquidates_and_never_negative():
    pf = Portfolio(cash=0.01, positions={"SOL": 0.1})    # 10 $ de SOL
    unpaid, trades = pf.pay_costs(1.0, PRICES, FEE)
    assert unpaid == 0
    assert trades and trades[0].reason == "LIQUIDATION"
    assert pf.cash >= 0
    assert pf.total_value(PRICES) == pytest.approx(10.01 - 1.0 - trades[0].fee)


def test_pay_costs_unpaid_when_broke():
    pf = Portfolio(cash=0.5)
    unpaid, _ = pf.pay_costs(2.0, PRICES, FEE)
    assert unpaid == pytest.approx(1.5)
    assert pf.cash == 0.0


def test_pay_costs_float_residue_is_not_debt():
    # Régression : après une liquidation partielle, un résidu d'arrondi ne doit
    # pas être compté comme impayé (sinon le kill switch se déclenche à tort).
    for cash in (0.0, 1e-7, 0.000123456789, 0.0099999):
        pf = Portfolio(cash=cash, positions={"SOL": 0.2351234567})
        unpaid, _ = pf.pay_costs(0.000231234567, PRICES, FEE)
        assert unpaid == 0.0
        assert not pf.is_dead(PRICES)
