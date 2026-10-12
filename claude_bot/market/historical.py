"""Données historiques pour le backtest : téléchargement, cache CSV et rejeu.

`HistoricalMarket` rejoue l'historique bougie par bougie : à chaque appel de
`snapshot()`, l'agent ne voit QUE les bougies déjà closes (pas de triche par
lecture du futur), puis le temps avance d'un pas.
"""

from __future__ import annotations

import csv
import logging
import math
import random
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from market.binance import Candle, MarketClient, MarketDataError, MarketSnapshot

log = logging.getLogger(__name__)

INTERVAL_SECONDS = {"1h": 3600, "2h": 7200, "4h": 14400, "6h": 21600, "12h": 43200, "1d": 86400}


def _iso(ms: int) -> str:
    return datetime.fromtimestamp(ms / 1000, tz=timezone.utc).isoformat(timespec="seconds")


# --------------------------------------------------------------------- cache CSV
def save_csv(path: Path, candles: list[Candle]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", newline="") as fh:
        w = csv.writer(fh)
        w.writerow(["open_time", "open", "high", "low", "close", "volume"])
        for c in candles:
            w.writerow([c.open_time, c.open, c.high, c.low, c.close, c.volume])


def load_csv(path: Path) -> list[Candle]:
    with open(path, newline="") as fh:
        return [Candle(int(r["open_time"]), float(r["open"]), float(r["high"]), float(r["low"]),
                       float(r["close"]), float(r["volume"])) for r in csv.DictReader(fh)]


def download_klines(client: MarketClient, asset: str, quote: str, interval: str,
                    start_ms: int, end_ms: int) -> list[Candle]:
    """Télécharge l'historique Binance par pages de 1000 bougies."""
    out: list[Candle] = []
    cursor = start_ms
    bases = client.cfg.get("binance_base_urls", ["https://api.binance.com"])
    while cursor < end_ms:
        page = None
        for base in bases:
            try:
                page = client._get_json(f"{base}/api/v3/klines", {
                    "symbol": f"{asset}{quote}", "interval": interval,
                    "startTime": cursor, "endTime": end_ms, "limit": 1000})
                break
            except MarketDataError as exc:
                log.warning("%s : %s", base, exc)
        if page is None:
            raise MarketDataError(f"impossible de télécharger l'historique de {asset}")
        if not page:
            break
        out += [Candle(int(k[0]), float(k[1]), float(k[2]), float(k[3]), float(k[4]), float(k[5])) for k in page]
        cursor = int(page[-1][0]) + INTERVAL_SECONDS[interval] * 1000
    return out


def get_history(client: MarketClient, assets: list[str], quote: str, interval: str, days: int,
                cache_dir: Path, end_ms: int | None = None) -> dict[str, list[Candle]]:
    """Historique de `days` jours (+ marge pour l'historique initial), mis en cache."""
    end_ms = end_ms or int(datetime.now(timezone.utc).timestamp() // 3600 * 3600 * 1000)
    start_ms = end_ms - (days + 5) * 86_400_000
    data = {}
    for a in assets:
        path = cache_dir / f"{a}{quote}_{interval}_{start_ms}_{end_ms}.csv"
        if path.exists():
            data[a] = load_csv(path)
        else:
            log.info("Téléchargement de l'historique %s (%s, %d jours)…", a, interval, days)
            data[a] = download_klines(client, a, quote, interval, start_ms, end_ms)
            save_csv(path, data[a])
    return align(data)


def align(data: dict[str, list[Candle]]) -> dict[str, list[Candle]]:
    """Ne garde que les horodatages présents pour tous les actifs."""
    common = set.intersection(*(set(c.open_time for c in cs) for cs in data.values()))
    return {a: [c for c in cs if c.open_time in common] for a, cs in data.items()}


def synthetic_history(assets: list[str], interval: str, days: int, seed: int | None = 42) -> dict[str, list[Candle]]:
    """Historique fictif (régimes haussiers / baissiers aléatoires) pour tester hors ligne."""
    rng = random.Random(seed)
    step = INTERVAL_SECONDS[interval]
    n = (days + 5) * 86400 // step
    start = int(datetime(2025, 1, 1, tzinfo=timezone.utc).timestamp()) * 1000
    base = {"BTC": 65_000.0, "ETH": 3_200.0, "SOL": 150.0}
    vol = 0.6 * math.sqrt(step / (365 * 86400))          # ~60 % de volatilité annuelle
    out = {}
    for a in assets:
        p, drift, candles = base.get(a, 100.0), 0.0, []
        for i in range(n):
            if i % 200 == 0:                               # changement de régime
                drift = rng.gauss(0, 0.8) * step / (365 * 86400)
            o = p
            p = p * math.exp(drift + rng.gauss(0, vol))
            candles.append(Candle(start + i * step * 1000, o, max(o, p) * 1.002, min(o, p) * 0.998, p, 0.0))
        out[a] = candles
    return out


# ------------------------------------------------------------------------ rejeu
class HistoricalMarket:
    def __init__(self, history: dict[str, list[Candle]], lookback: int = 24):
        self.history = history
        self.lookback = lookback
        self.assets = list(history)
        self.length = min(len(c) for c in history.values())
        if self.length <= lookback:
            raise ValueError("historique trop court pour le backtest")
        self.index = lookback          # première bougie « actuelle »

    @property
    def steps(self) -> int:
        """Nombre de cycles jouables."""
        return self.length - self.lookback

    def snapshot(self) -> MarketSnapshot:
        if self.index >= self.length:
            raise MarketDataError("fin de l'historique")
        i = self.index
        self.index += 1
        # Fenêtre des bougies closes jusqu'à i inclus ; le prix « actuel » = clôture de i.
        window = {a: self.history[a][i - self.lookback + 1:i + 1] for a in self.assets}
        prices = {a: w[-1].close for a, w in window.items()}
        ts_ms = window[self.assets[0]][-1].open_time
        return MarketSnapshot(prices, window, source="historical", ts=_iso(ts_ms))

    def prices_at(self, i: int) -> dict[str, float]:
        return {a: self.history[a][i].close for a in self.assets}
