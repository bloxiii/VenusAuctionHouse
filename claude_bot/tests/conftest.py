"""Fixtures communes aux tests."""

import sys
from pathlib import Path

import pytest

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from agent.config import load_config  # noqa: E402
from agent.llm import LLMResult  # noqa: E402
from market.binance import MarketDataError, MarketSnapshot  # noqa: E402


@pytest.fixture
def cfg(tmp_path):
    c = load_config()
    c["storage"]["db_path"] = str(tmp_path / "test_ledger.db")
    return c


class FixedMarket:
    """Marché de test : prix fixes, ou panne si prices=None."""

    def __init__(self, prices):
        self.prices = prices

    def snapshot(self):
        if self.prices is None:
            raise MarketDataError("panne de test")
        return MarketSnapshot(dict(self.prices), {}, source="test")


class ScriptedDecider:
    """Renvoie une suite de réponses textuelles prédéfinies, avec un coût fixe."""

    def __init__(self, texts, cost=0.0):
        self.texts = list(texts)
        self.cost = cost
        self.calls = 0

    def decide(self, system_prompt, user_message):
        self.calls += 1
        text = self.texts.pop(0) if self.texts else '{"action":"HOLD","asset":null,"amount_usd":0,"reasoning":"x"}'
        return LLMResult(text=text, input_tokens=100, output_tokens=50, cost_usd=self.cost)


PRICES = {"BTC": 50_000.0, "ETH": 2_500.0, "SOL": 100.0}
