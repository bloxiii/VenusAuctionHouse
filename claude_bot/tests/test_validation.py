"""Validation stricte du JSON renvoyé par le modèle."""

import pytest

from agent.validation import DecisionValidationError, parse_decision

ASSETS = ["BTC", "ETH", "SOL"]


def ok(text):
    return parse_decision(text, ASSETS)


def test_valid_buy():
    d = ok('{"action":"BUY","asset":"ETH","amount_usd":12.5,"reasoning":"tendance haussière"}')
    assert (d.action, d.asset, d.amount_usd) == ("BUY", "ETH", 12.5)


def test_hold_is_normalized():
    d = ok('{"action":"HOLD","asset":"BTC","amount_usd":99,"reasoning":"attente"}')
    assert (d.action, d.asset, d.amount_usd) == ("HOLD", None, 0.0)


def test_code_fence_tolerated():
    d = ok('```json\n{"action":"SELL","asset":"SOL","amount_usd":3,"reasoning":"r"}\n```')
    assert d.action == "SELL"


@pytest.mark.parametrize("text", [
    "",
    None,
    "je pense qu'il faut acheter",
    '[1, 2]',
    '{"action":"BUY","asset":"BTC","amount_usd":5}',                                     # clé manquante
    '{"action":"BUY","asset":"BTC","amount_usd":5,"reasoning":"r","leverage":10}',      # clé en trop
    '{"action":"SHORT","asset":"BTC","amount_usd":5,"reasoning":"r"}',                  # action inconnue
    '{"action":"BUY","asset":"DOGE","amount_usd":5,"reasoning":"r"}',                   # actif interdit
    '{"action":"BUY","asset":"BTC","amount_usd":-5,"reasoning":"r"}',                   # négatif
    '{"action":"BUY","asset":"BTC","amount_usd":"5","reasoning":"r"}',                  # chaîne
    '{"action":"BUY","asset":"BTC","amount_usd":true,"reasoning":"r"}',                 # booléen
    '{"action":"BUY","asset":"BTC","amount_usd":NaN,"reasoning":"r"}',                  # NaN
    '{"action":"BUY","asset":"BTC","amount_usd":Infinity,"reasoning":"r"}',             # infini
    '{"action":"BUY","asset":null,"amount_usd":5,"reasoning":"r"}',                     # BUY sans actif
    '{"action":"SELL","asset":"ETH","amount_usd":0,"reasoning":"r"}',                   # montant nul
    '{"action":"BUY","asset":"BTC","amount_usd":5,"reasoning":42}',                     # reasoning non texte
    '{"action":"buy","asset":"BTC","amount_usd":5,"reasoning":"r"}',                    # casse stricte
])
def test_invalid_responses_rejected(text):
    with pytest.raises(DecisionValidationError):
        ok(text)


def test_long_reasoning_truncated():
    d = ok('{"action":"HOLD","asset":null,"amount_usd":0,"reasoning":"' + "a" * 5000 + '"}')
    assert len(d.reasoning) <= 600
