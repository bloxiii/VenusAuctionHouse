#!/usr/bin/env python3
"""Lance l'agent CLAUDE BOT / SURVIVAL MODE (paper trading uniquement).

Exemples :
    python run_agent.py                      # vrai modèle, cycles de 10 min
    python run_agent.py --dry-run --fast     # sans API, cycles de quelques secondes
    python run_agent.py --reset              # efface le ledger et recommence à 50 $
"""

from __future__ import annotations

import argparse
import logging
import sys

from agent.config import load_config
from agent.loop import Agent
from portfolio.ledger import Ledger


def main(argv: list[str] | None = None) -> int:
    p = argparse.ArgumentParser(description="Agent de paper trading autonome — CLAUDE BOT / SURVIVAL MODE")
    p.add_argument("--config", help="chemin de config.yaml")
    p.add_argument("--dry-run", action="store_true", help="stratégie factice, aucun appel API")
    p.add_argument("--fast", action="store_true", help="cycles de quelques secondes")
    p.add_argument("--max-cycles", type=int, help="remplace game.max_cycles")
    p.add_argument("--db", help="chemin du ledger SQLite (remplace storage.db_path)")
    p.add_argument("--reset", action="store_true", help="efface le ledger et recommence")
    p.add_argument("--simulated-market", action="store_true",
                   help="prix fictifs (marche aléatoire) au lieu des API publiques — test hors ligne")
    args = p.parse_args(argv)

    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)-8s %(message)s",
                        datefmt="%H:%M:%S")
    log = logging.getLogger("claude_bot")

    cfg = load_config(args.config)
    if args.max_cycles:
        cfg["game"]["max_cycles"] = args.max_cycles
    if args.db:
        cfg["storage"]["db_path"] = args.db

    ledger = Ledger(cfg["storage"]["db_path"])
    if args.reset:
        ledger.reset()
        log.info("Ledger réinitialisé.")

    if args.dry_run:
        from agent.dummy import DummyDecider
        decider = DummyDecider(cfg)
        log.info("Mode DRY-RUN : stratégie factice '%s', aucun crédit API dépensé.", cfg["dry_run"]["strategy"])
    else:
        from agent.llm import ClaudeDecider, LLMError
        try:
            decider = ClaudeDecider(cfg)
        except LLMError as exc:
            log.error("%s", exc)
            return 2
        log.info("Modèle : %s", cfg["model"]["name"])

    if args.simulated_market:
        from market.binance import SimulatedMarket
        market = SimulatedMarket(cfg["game"]["assets"], seed=cfg["dry_run"].get("seed"))
        log.warning("Marché SIMULÉ : les prix sont fictifs (test uniquement).")
    else:
        from market.binance import MarketClient
        market = MarketClient(cfg["market"], cfg["game"]["assets"], cfg["game"]["quote"])

    interval = cfg["game"]["fast_interval_seconds"] if args.fast else cfg["game"]["cycle_interval_seconds"]
    log.info("CLAUDE BOT / SURVIVAL MODE — paper trading — intervalle %ss", interval)
    agent = Agent(cfg, ledger, decider, market)
    try:
        state = agent.run(interval)
    except KeyboardInterrupt:
        log.info("Arrêt demandé. L'état est sauvegardé : relancez pour reprendre.")
        return 0
    finally:
        ledger.close()
    log.info("Fin : statut %s — %s", state["status"], state.get("cause") or "")
    return 0


if __name__ == "__main__":
    sys.exit(main())
