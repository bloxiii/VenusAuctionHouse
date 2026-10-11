#!/usr/bin/env python3
"""Lance le tableau de bord sur http://localhost:8000"""

from __future__ import annotations

import argparse

from agent.config import load_config
from dashboard.app import create_app


def main() -> None:
    p = argparse.ArgumentParser(description="Dashboard CLAUDE BOT / SURVIVAL MODE")
    p.add_argument("--config", help="chemin de config.yaml")
    p.add_argument("--db", help="chemin du ledger SQLite")
    p.add_argument("--port", type=int)
    args = p.parse_args()
    cfg = load_config(args.config)
    if args.db:
        cfg["storage"]["db_path"] = args.db
    port = args.port or cfg["dashboard"]["port"]
    print(f"Dashboard : http://localhost:{port}")
    create_app(cfg).run(host=cfg["dashboard"]["host"], port=port, debug=False)


if __name__ == "__main__":
    main()
