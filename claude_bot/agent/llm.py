"""Appel au modèle Claude (SDK officiel `anthropic`) et calcul du coût réel."""

from __future__ import annotations

import logging
import os
from dataclasses import dataclass
from typing import Any

from agent.validation import decision_schema

log = logging.getLogger(__name__)


class LLMError(RuntimeError):
    """L'appel au modèle a échoué (réseau, quota, clé…). Aucun token facturé."""


@dataclass
class LLMResult:
    text: str | None
    input_tokens: int = 0
    output_tokens: int = 0
    cache_creation_tokens: int = 0
    cache_read_tokens: int = 0
    cost_usd: float = 0.0
    stop_reason: str | None = None
    estimated: bool = False   # True en --dry-run (coût estimé, pas facturé)


def compute_cost(pricing: dict[str, float], input_tokens: int, output_tokens: int,
                 cache_creation_tokens: int = 0, cache_read_tokens: int = 0) -> float:
    """Coût en USD à partir des tokens et des tarifs ($ / million de tokens).

    Note : les tokens de réflexion (thinking) sont comptés dans output_tokens.
    """
    return (input_tokens * pricing["input_per_mtok"]
            + output_tokens * pricing["output_per_mtok"]
            + cache_creation_tokens * pricing.get("cache_write_per_mtok", pricing["input_per_mtok"])
            + cache_read_tokens * pricing.get("cache_read_per_mtok", pricing["input_per_mtok"])) / 1_000_000


class ClaudeDecider:
    """Demande une décision de trading à Claude."""

    def __init__(self, cfg: dict[str, Any], schema_assets: list[str] | None = None):
        import anthropic  # import local : --dry-run fonctionne sans clé API

        if not os.environ.get("ANTHROPIC_API_KEY"):
            raise LLMError("Variable d'environnement ANTHROPIC_API_KEY absente (ou utilisez --dry-run).")
        self._anthropic = anthropic
        self.cfg = cfg
        # Noms d'actifs autorisés dans le schéma (différents en mode anonymisé).
        self.schema_assets = schema_assets or cfg["game"]["assets"]
        m = cfg["model"]
        # La clé est lue automatiquement depuis ANTHROPIC_API_KEY par le SDK.
        self.client = anthropic.Anthropic(timeout=float(m.get("timeout_seconds", 60)), max_retries=2)

    def decide(self, system_prompt: str, user_message: str) -> LLMResult:
        m = self.cfg["model"]
        output_config: dict[str, Any] = {"effort": m.get("effort", "low")}
        if m.get("structured_output", True):
            output_config["format"] = {"type": "json_schema",
                                       "schema": decision_schema(self.schema_assets)}
        a = self._anthropic
        try:
            resp = self.client.messages.create(
                model=m["name"],
                max_tokens=int(m["max_tokens"]),
                system=system_prompt,
                messages=[{"role": "user", "content": user_message}],
                output_config=output_config,
            )
        except a.AuthenticationError as exc:
            raise LLMError(f"clé API refusée : {exc}") from exc
        except a.RateLimitError as exc:
            raise LLMError(f"limite de requêtes atteinte : {exc}") from exc
        except a.APIStatusError as exc:
            raise LLMError(f"erreur API {exc.status_code} : {exc}") from exc
        except a.APIConnectionError as exc:
            raise LLMError(f"connexion à l'API impossible : {exc}") from exc

        u = resp.usage
        tokens = dict(
            input_tokens=u.input_tokens or 0,
            output_tokens=u.output_tokens or 0,
            cache_creation_tokens=getattr(u, "cache_creation_input_tokens", 0) or 0,
            cache_read_tokens=getattr(u, "cache_read_input_tokens", 0) or 0,
        )
        # On ne lit que les blocs texte (les blocs de réflexion sont ignorés).
        text = "".join(b.text for b in resp.content if b.type == "text") or None
        if resp.stop_reason == "refusal":
            log.warning("Le modèle a refusé de répondre (stop_reason=refusal)")
            text = None
        return LLMResult(text=text, cost_usd=compute_cost(m["pricing"], **tokens),
                         stop_reason=resp.stop_reason, **tokens)
