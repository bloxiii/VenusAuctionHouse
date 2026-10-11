"""Boucle de l'agent : kill switch, HOLD forcé, cycles sautés, reprise après redémarrage."""

import json

import pytest

from agent.llm import LLMError
from agent.loop import Agent
from portfolio.ledger import Ledger
from tests.conftest import PRICES, FixedMarket, ScriptedDecider

BUY_BTC = '{"action":"BUY","asset":"BTC","amount_usd":20,"reasoning":"achat"}'


def make_agent(cfg, decider, prices=PRICES):
    ledger = Ledger(cfg["storage"]["db_path"])
    return Agent(cfg, ledger, decider, FixedMarket(prices)), ledger


# ------------------------------------------------------------------ kill switch
def test_kill_switch_when_costs_exceed_value(cfg):
    agent, ledger = make_agent(cfg, ScriptedDecider([], cost=30.0))   # appel API hors de prix
    state = agent.run(interval_seconds=0)
    assert state["status"] == "DEAD"
    assert state["cycle_count"] == 2          # 50 -> 20 -> mort au 2e cycle
    assert ledger.load_state()["status"] == "DEAD"
    assert "0.000000" in ledger.load_state()["cause"]
    assert state["portfolio"].cash == 0.0     # jamais négatif


def test_kill_switch_is_permanent(cfg):
    agent, ledger = make_agent(cfg, ScriptedDecider([], cost=100.0))
    agent.run(interval_seconds=0)
    decider = ScriptedDecider([])
    agent2 = Agent(cfg, ledger, decider, FixedMarket(PRICES))
    state = agent2.run(interval_seconds=0)
    assert state["status"] == "DEAD" and decider.calls == 0


def test_kill_switch_with_positions_liquidated(cfg):
    # Achat puis coûts énormes : les positions sont liquidées pour payer, puis mort.
    agent, ledger = make_agent(cfg, ScriptedDecider([BUY_BTC], cost=0.0))
    state = agent.load_or_init_state()
    state = agent.run_cycle(state)
    assert state["portfolio"].positions
    agent.decider = ScriptedDecider([], cost=1_000.0)
    state = agent.run_cycle(state)
    assert state["status"] == "DEAD"
    sides = [t["reason"] for t in ledger.recent_transactions(10)]
    assert "LIQUIDATION" in sides


# --------------------------------------------------------- erreurs et HOLD forcé
def test_invalid_json_forces_hold(cfg):
    agent, ledger = make_agent(cfg, ScriptedDecider(["pas du json"], cost=0.001))
    state = agent.run_cycle(agent.load_or_init_state())
    c = ledger.recent_cycles(1)[0]
    assert c["status"] == "INVALID"
    assert json.loads(c["applied_json"])["action"] == "HOLD"
    assert c["raw_decision"] == "pas du json"
    assert state["portfolio"].cash == pytest.approx(50.0 - 0.001)   # le coût API est payé quand même


def test_market_failure_skips_cycle(cfg):
    decider = ScriptedDecider([BUY_BTC])
    agent, ledger = make_agent(cfg, decider, prices=None)
    agent.run_cycle(agent.load_or_init_state())
    c = ledger.recent_cycles(1)[0]
    assert c["status"] == "SKIPPED" and decider.calls == 0   # aucune décision à l'aveugle


def test_llm_error_forces_hold(cfg):
    class Failing:
        def decide(self, *_):
            raise LLMError("réseau coupé")
    agent, ledger = make_agent(cfg, Failing())
    state = agent.run_cycle(agent.load_or_init_state())
    assert ledger.recent_cycles(1)[0]["status"] == "LLM_ERROR"
    assert state["portfolio"].cash == 50.0


def test_oversized_order_clamped(cfg):
    agent, ledger = make_agent(cfg, ScriptedDecider(['{"action":"BUY","asset":"ETH","amount_usd":1000,"reasoning":"all in"}']))
    agent.run_cycle(agent.load_or_init_state())
    applied = json.loads(ledger.recent_cycles(1)[0]["applied_json"])
    assert applied["amount_usd"] == pytest.approx(25.0)
    assert ledger.recent_transactions(1)[0]["notional"] == pytest.approx(25.0)


def test_completes_after_max_cycles(cfg):
    cfg["game"]["max_cycles"] = 3
    agent, _ = make_agent(cfg, ScriptedDecider([], cost=0.0001))
    state = agent.run(interval_seconds=0)
    assert state["status"] == "COMPLETED" and state["cycle_count"] == 3


# ---------------------------------------------------------------------- reprise
def test_resume_after_restart(cfg):
    cfg["game"]["max_cycles"] = 5
    agent, ledger = make_agent(cfg, ScriptedDecider([BUY_BTC], cost=0.01))
    state = agent.load_or_init_state()
    for _ in range(2):
        state = agent.run_cycle(state)
    snapshot = (state["portfolio"].to_dict(), state["total_api_cost"], state["total_fees"])
    ledger.close()   # « arrêt » du programme

    decider = ScriptedDecider([], cost=0.01)
    agent2, ledger2 = make_agent(cfg, decider)
    state2 = agent2.load_or_init_state()
    assert state2["cycle_count"] == 2
    assert state2["portfolio"].to_dict() == snapshot[0]          # cash, positions, coût de revient
    assert state2["total_api_cost"] == pytest.approx(snapshot[1])
    assert state2["total_fees"] == pytest.approx(snapshot[2])
    state2 = agent2.run(interval_seconds=0)
    assert state2["status"] == "COMPLETED"
    assert decider.calls == 3                          # cycles 3, 4, 5 seulement
    numbers = [c["cycle_number"] for c in reversed(ledger2.recent_cycles(10))]
    assert numbers == [1, 2, 3, 4, 5]
