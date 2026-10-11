"""Chargement et validation de config.yaml."""

from __future__ import annotations

import copy
from pathlib import Path
from typing import Any

import yaml

PROJECT_ROOT = Path(__file__).resolve().parent.parent
DEFAULT_CONFIG_PATH = PROJECT_ROOT / "config.yaml"


class ConfigError(ValueError):
    """Configuration invalide."""


def _require(cond: bool, msg: str) -> None:
    if not cond:
        raise ConfigError(msg)


def validate_config(cfg: dict[str, Any]) -> dict[str, Any]:
    """Vérifie les valeurs critiques pour échouer tôt plutôt qu'en plein cycle."""
    g, f, r, m = cfg["game"], cfg["fees"], cfg["risk"], cfg["model"]
    _require(g["initial_balance_usd"] > 0, "game.initial_balance_usd doit être > 0")
    _require(g["max_cycles"] > 0, "game.max_cycles doit être > 0")
    _require(g["cycle_interval_seconds"] > 0, "game.cycle_interval_seconds doit être > 0")
    _require(len(g["assets"]) > 0, "game.assets ne peut pas être vide")
    _require(0 <= f["trade_fee_rate"] < 1, "fees.trade_fee_rate doit être dans [0, 1[")
    _require(0 < r["max_trade_fraction"] <= 1, "risk.max_trade_fraction doit être dans ]0, 1]")
    _require(r["on_violation"] in ("clamp", "reject"), "risk.on_violation: clamp ou reject")
    _require(cfg["survival"]["rent_per_month_usd"] >= 0, "survival.rent_per_month_usd >= 0")
    for k in ("input_per_mtok", "output_per_mtok"):
        _require(m["pricing"][k] >= 0, f"model.pricing.{k} >= 0")
    _require(cfg["dry_run"]["strategy"] in ("random", "hold"), "dry_run.strategy: random ou hold")
    # Les symboles d'actifs sont normalisés en majuscules.
    g["assets"] = [a.upper() for a in g["assets"]]
    return cfg


def load_config(path: str | Path | None = None) -> dict[str, Any]:
    path = Path(path) if path else DEFAULT_CONFIG_PATH
    with open(path, encoding="utf-8") as fh:
        cfg = yaml.safe_load(fh)
    cfg = validate_config(copy.deepcopy(cfg))
    # Le chemin de la base est relatif au dossier du projet, pas au cwd.
    db = Path(cfg["storage"]["db_path"])
    if not db.is_absolute():
        cfg["storage"]["db_path"] = str(PROJECT_ROOT / db)
    return cfg


def rent_per_cycle(cfg: dict[str, Any]) -> float:
    """Loyer mensuel converti au prorata d'un cycle nominal (mois = 30 jours)."""
    monthly = float(cfg["survival"]["rent_per_month_usd"])
    seconds = float(cfg["game"]["cycle_interval_seconds"])
    return monthly * seconds / (30 * 24 * 3600)
