"""Données de marché depuis des API publiques sans clé (Binance, puis CoinGecko).

Toute donnée externe est convertie en nombres et vérifiée : rien de ce qui
vient du réseau n'est transmis tel quel au modèle. Si une donnée manque,
`MarketDataError` est levée et le cycle est sauté (jamais de décision à l'aveugle).
"""

from __future__ import annotations

import logging
import math
import random
import time
from dataclasses import dataclass, field
from typing import Any

import requests

log = logging.getLogger(__name__)


class MarketDataError(RuntimeError):
    """Données de marché indisponibles ou incohérentes."""


@dataclass
class Candle:
    open_time: int   # ms epoch
    open: float
    high: float
    low: float
    close: float
    volume: float


@dataclass
class MarketSnapshot:
    prices: dict[str, float]
    history: dict[str, list[Candle]] = field(default_factory=dict)
    source: str = ""

    def summary(self) -> dict[str, Any]:
        """Vue compacte (variations 1 h / 24 h, plus haut / bas) pour le prompt."""
        out: dict[str, Any] = {}
        for asset, price in self.prices.items():
            candles = self.history.get(asset, [])
            item: dict[str, Any] = {"price": round(price, 6)}
            if candles:
                first, last = candles[0].open, candles[-1].close
                item["change_24h_pct"] = round((last / first - 1) * 100, 3) if first else None
                if len(candles) >= 2:
                    prev = candles[-2].close
                    item["change_1h_pct"] = round((last / prev - 1) * 100, 3) if prev else None
                item["high_24h"] = round(max(c.high for c in candles), 6)
                item["low_24h"] = round(min(c.low for c in candles), 6)
                item["closes_1h"] = [round(c.close, 6) for c in candles]
            out[asset] = item
        return out


def _num(x: Any) -> float:
    """Convertit en float positif fini, sinon MarketDataError."""
    try:
        v = float(x)
    except (TypeError, ValueError) as exc:
        raise MarketDataError(f"valeur non numérique : {x!r}") from exc
    if not math.isfinite(v) or v < 0:
        raise MarketDataError(f"valeur invalide : {v}")
    return v


class MarketClient:
    def __init__(self, cfg: dict[str, Any], assets: list[str], quote: str = "USDT",
                 session: requests.Session | None = None):
        self.cfg = cfg
        self.assets = assets
        self.quote = quote
        self.session = session or requests.Session()
        self.session.headers["User-Agent"] = "claude-bot-paper-trading/1.0"

    # ------------------------------------------------------------- HTTP robuste
    def _get_json(self, url: str, params: dict[str, Any] | None = None) -> Any:
        retries = int(self.cfg.get("max_retries", 3))
        delay = float(self.cfg.get("backoff_seconds", 2))
        timeout = float(self.cfg.get("request_timeout_seconds", 10))
        last_err: Exception | None = None
        for attempt in range(1, retries + 1):
            try:
                resp = self.session.get(url, params=params, timeout=timeout)
                if resp.status_code == 429 or resp.status_code >= 500:
                    # Limite de requêtes ou erreur serveur : on attend et on réessaie.
                    wait = float(resp.headers.get("Retry-After", delay))
                    last_err = MarketDataError(f"HTTP {resp.status_code} sur {url}")
                    log.warning("%s (tentative %d/%d), attente %.1fs", last_err, attempt, retries, wait)
                    time.sleep(min(wait, 60))
                    delay *= 2
                    continue
                if resp.status_code != 200:
                    raise MarketDataError(f"HTTP {resp.status_code} sur {url}")
                return resp.json()
            except (requests.RequestException, ValueError) as exc:
                last_err = exc
                log.warning("Erreur réseau %s (tentative %d/%d) : %s", url, attempt, retries, exc)
                if attempt < retries:
                    time.sleep(delay)
                    delay *= 2
        raise MarketDataError(f"échec après {retries} tentatives : {last_err}")

    # ------------------------------------------------------------------ Binance
    def _from_binance(self, base: str) -> MarketSnapshot:
        prices: dict[str, float] = {}
        history: dict[str, list[Candle]] = {}
        for asset in self.assets:
            symbol = f"{asset}{self.quote}"
            data = self._get_json(f"{base}/api/v3/ticker/price", {"symbol": symbol})
            prices[asset] = _num(data["price"])
            klines = self._get_json(f"{base}/api/v3/klines", {
                "symbol": symbol, "interval": self.cfg.get("kline_interval", "1h"),
                "limit": int(self.cfg.get("kline_limit", 24))})
            history[asset] = [Candle(int(k[0]), _num(k[1]), _num(k[2]), _num(k[3]), _num(k[4]), _num(k[5]))
                              for k in klines]
        return MarketSnapshot(prices, history, source=f"binance:{base}")

    # ---------------------------------------------------------------- CoinGecko
    def _from_coingecko(self) -> MarketSnapshot:
        base = self.cfg["coingecko_base_url"]
        ids = self.cfg["coingecko_ids"]
        data = self._get_json(f"{base}/simple/price",
                              {"ids": ",".join(ids[a] for a in self.assets), "vs_currencies": "usd"})
        prices = {a: _num(data[ids[a]]["usd"]) for a in self.assets}
        history: dict[str, list[Candle]] = {}
        for a in self.assets:
            chart = self._get_json(f"{base}/coins/{ids[a]}/market_chart", {"vs_currency": "usd", "days": 1})
            points = chart.get("prices", [])
            # CoinGecko donne des points ~5 min : on regroupe par heure (pseudo-bougies).
            hourly: dict[int, list[float]] = {}
            for t, p in points:
                hourly.setdefault(int(t) // 3_600_000, []).append(_num(p))
            history[a] = [Candle(h * 3_600_000, v[0], max(v), min(v), v[-1], 0.0)
                          for h, v in sorted(hourly.items())][-int(self.cfg.get("kline_limit", 24)):]
        return MarketSnapshot(prices, history, source="coingecko")

    # ----------------------------------------------------------------- public
    def snapshot(self) -> MarketSnapshot:
        errors: list[str] = []
        for source in self.cfg.get("sources", ["binance", "coingecko"]):
            try:
                if source == "binance":
                    for base in self.cfg.get("binance_base_urls", ["https://api.binance.com"]):
                        try:
                            return self._validate(self._from_binance(base))
                        except (MarketDataError, KeyError, TypeError, IndexError) as exc:
                            errors.append(f"binance {base}: {exc}")
                elif source == "coingecko":
                    return self._validate(self._from_coingecko())
            except (MarketDataError, KeyError, TypeError, IndexError) as exc:
                errors.append(f"{source}: {exc}")
        raise MarketDataError("toutes les sources ont échoué : " + " | ".join(errors))

    def _validate(self, snap: MarketSnapshot) -> MarketSnapshot:
        for a in self.assets:
            if not snap.prices.get(a):
                raise MarketDataError(f"prix manquant pour {a}")
        return snap


class SimulatedMarket:
    """Marché fictif (marche aléatoire) pour tester la boucle hors ligne.

    Utilisé uniquement avec --simulated-market : utile quand le réseau bloque
    les API publiques (CI, bac à sable). Jamais utilisé par défaut.
    """

    START = {"BTC": 65_000.0, "ETH": 3_200.0, "SOL": 150.0}

    def __init__(self, assets: list[str], seed: int | None = None, fail_rate: float = 0.0):
        self.rng = random.Random(seed)
        self.assets = assets
        self.fail_rate = fail_rate
        self.history: dict[str, list[Candle]] = {}
        now = int(time.time() // 3600 * 3_600_000)
        for a in assets:
            p = self.START.get(a, 100.0)
            candles = []
            for i in range(24):
                o = p
                p = p * math.exp(self.rng.gauss(0, 0.008))
                candles.append(Candle(now - (24 - i) * 3_600_000, o, max(o, p) * 1.002, min(o, p) * 0.998, p, 0.0))
            self.history[a] = candles

    def snapshot(self) -> MarketSnapshot:
        if self.rng.random() < self.fail_rate:
            raise MarketDataError("panne simulée de la source de marché")
        for a in self.assets:
            last = self.history[a][-1]
            p = last.close * math.exp(self.rng.gauss(0, 0.01))
            self.history[a] = self.history[a][1:] + [
                Candle(last.open_time + 3_600_000, last.close, max(last.close, p), min(last.close, p), p, 0.0)]
        prices = {a: self.history[a][-1].close for a in self.assets}
        return MarketSnapshot(prices, {a: list(c) for a, c in self.history.items()}, source="simulated")
