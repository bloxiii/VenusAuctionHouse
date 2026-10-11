"""Stratégie factice pour --dry-run : aucun appel API, aucun crédit dépensé."""

from __future__ import annotations

import json
import random
from typing import Any

from agent.llm import LLMResult, compute_cost


class DummyDecider:
    def __init__(self, cfg: dict[str, Any]):
        self.cfg = cfg
        self.strategy = cfg["dry_run"]["strategy"]
        self.rng = random.Random(cfg["dry_run"].get("seed"))
        self.assets = cfg["game"]["assets"]

    def _decision(self) -> dict[str, Any]:
        if self.strategy == "hold" or self.rng.random() < 0.4:
            return {"action": "HOLD", "asset": None, "amount_usd": 0,
                    "reasoning": "[dry-run] Stratégie factice : j'attends."}
        action = self.rng.choice(["BUY", "SELL"])
        asset = self.rng.choice(self.assets)
        # Montants parfois volontairement excessifs pour exercer les limites de risque.
        amount = round(self.rng.uniform(1, 40), 2)
        return {"action": action, "asset": asset, "amount_usd": amount,
                "reasoning": f"[dry-run] Décision aléatoire : {action} {asset} pour {amount} $."}

    def decide(self, system_prompt: str, user_message: str) -> LLMResult:
        text = json.dumps(self._decision(), ensure_ascii=False)
        result = LLMResult(text=text, stop_reason="end_turn", estimated=True)
        if self.cfg["dry_run"].get("simulate_api_cost", True):
            # Estimation grossière : ~4 caractères par token, + ~150 tokens de réflexion.
            result.input_tokens = (len(system_prompt) + len(user_message)) // 4
            result.output_tokens = len(text) // 4 + 150
            result.cost_usd = compute_cost(self.cfg["model"]["pricing"], result.input_tokens, result.output_tokens)
        return result
