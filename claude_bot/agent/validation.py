"""Validation stricte de la réponse JSON du modèle.

Format attendu (et rien d'autre) :
    {"action": "BUY"|"SELL"|"HOLD", "asset": "BTC"|"ETH"|"SOL"|null,
     "amount_usd": nombre, "reasoning": "explication courte"}

Toute réponse non conforme lève `DecisionValidationError` ; la boucle applique
alors un HOLD forcé et enregistre l'erreur.
"""

from __future__ import annotations

import json
import math
import re
from dataclasses import dataclass
from typing import Any

ACTIONS = ("BUY", "SELL", "HOLD")
REQUIRED_KEYS = {"action", "asset", "amount_usd", "reasoning"}
MAX_REASONING_CHARS = 600
_FENCE = re.compile(r"^```(?:json)?\s*(.*?)\s*```$", re.DOTALL)


class DecisionValidationError(ValueError):
    pass


@dataclass
class Decision:
    action: str
    asset: str | None
    amount_usd: float
    reasoning: str

    def as_dict(self) -> dict[str, Any]:
        return {"action": self.action, "asset": self.asset,
                "amount_usd": self.amount_usd, "reasoning": self.reasoning}


def decision_schema(assets: list[str]) -> dict[str, Any]:
    """Schéma JSON envoyé à l'API (sorties structurées)."""
    return {
        "type": "object",
        "properties": {
            "action": {"type": "string", "enum": list(ACTIONS)},
            "asset": {"anyOf": [{"type": "string", "enum": list(assets)}, {"type": "null"}]},
            "amount_usd": {"type": "number"},
            "reasoning": {"type": "string"},
        },
        "required": ["action", "asset", "amount_usd", "reasoning"],
        "additionalProperties": False,
    }


def parse_decision(text: str | None, assets: list[str]) -> Decision:
    if text is None or not str(text).strip():
        raise DecisionValidationError("réponse vide")
    raw = str(text).strip()
    m = _FENCE.match(raw)          # tolère un bloc ```json ... ``` autour de l'objet
    if m:
        raw = m.group(1)
    try:
        data = json.loads(raw)
    except json.JSONDecodeError as exc:
        raise DecisionValidationError(f"JSON invalide : {exc.msg}") from exc
    if not isinstance(data, dict):
        raise DecisionValidationError("la réponse doit être un objet JSON")

    keys = set(data)
    if keys != REQUIRED_KEYS:
        missing, extra = REQUIRED_KEYS - keys, keys - REQUIRED_KEYS
        raise DecisionValidationError(f"clés invalides (manquantes={sorted(missing)}, en trop={sorted(extra)})")

    action = data["action"]
    if action not in ACTIONS:
        raise DecisionValidationError(f"action inconnue : {action!r}")

    asset = data["asset"]
    if asset is not None and asset not in assets:
        raise DecisionValidationError(f"actif non autorisé : {asset!r}")

    amount = data["amount_usd"]
    # bool est un sous-type de int en Python : on l'exclut explicitement.
    if isinstance(amount, bool) or not isinstance(amount, (int, float)) or not math.isfinite(amount):
        raise DecisionValidationError(f"amount_usd doit être un nombre fini : {amount!r}")
    if amount < 0:
        raise DecisionValidationError("amount_usd ne peut pas être négatif")

    reasoning = data["reasoning"]
    if not isinstance(reasoning, str):
        raise DecisionValidationError("reasoning doit être une chaîne")
    reasoning = reasoning.strip()[:MAX_REASONING_CHARS]

    if action == "HOLD":
        return Decision("HOLD", None, 0.0, reasoning)
    if asset is None:
        raise DecisionValidationError(f"{action} exige un actif")
    if amount <= 0:
        raise DecisionValidationError(f"{action} exige amount_usd > 0")
    return Decision(action, asset, float(amount), reasoning)
