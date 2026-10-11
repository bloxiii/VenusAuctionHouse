"""Portefeuille virtuel : cash, positions, frais, limites de risque, kill switch.

Toutes les règles de sécurité vivent ici. Le modèle ne fait que *proposer* une
décision ; ce module décide de ce qui est réellement exécuté.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any

# Tolérance numérique pour éviter les poussières de float (ex. -1e-17 $).
EPS = 1e-9


@dataclass
class Trade:
    """Transaction simulée effectivement exécutée."""

    side: str            # BUY | SELL
    asset: str
    qty: float
    price: float
    notional: float      # montant brut en USD (qty * price)
    fee: float
    reason: str = "AGENT"  # AGENT | LIQUIDATION (vente forcée pour payer les coûts)


@dataclass
class RiskResult:
    """Résultat de l'application des limites de risque à une décision."""

    action: str                    # BUY | SELL | HOLD
    asset: str | None
    amount_usd: float
    notes: list[str] = field(default_factory=list)

    def as_dict(self) -> dict[str, Any]:
        return {"action": self.action, "asset": self.asset,
                "amount_usd": round(self.amount_usd, 8), "notes": self.notes}


@dataclass
class Portfolio:
    cash: float
    positions: dict[str, float] = field(default_factory=dict)    # actif -> quantité
    cost_basis: dict[str, float] = field(default_factory=dict)   # actif -> USD investis (frais inclus)

    # ------------------------------------------------------------------ valeur
    def position_value(self, asset: str, prices: dict[str, float]) -> float:
        return self.positions.get(asset, 0.0) * prices.get(asset, 0.0)

    def total_value(self, prices: dict[str, float]) -> float:
        """Cash + positions valorisées au prix du marché."""
        return self.cash + sum(self.position_value(a, prices) for a in self.positions)

    # ------------------------------------------------------------------ risque
    def apply_risk_limits(self, action: str, asset: str | None, amount_usd: float,
                          prices: dict[str, float], fee_rate: float,
                          max_trade_fraction: float, min_trade_usd: float,
                          on_violation: str = "clamp") -> RiskResult:
        """Transforme une décision *validée* en décision *autorisée*.

        Règles : pas de vente à découvert, pas de levier (on ne dépense que le
        cash disponible, frais compris), une transaction <= max_trade_fraction
        de la valeur totale, montant minimal, cash jamais négatif.
        """
        if action == "HOLD" or asset is None:
            return RiskResult("HOLD", None, 0.0)
        notes: list[str] = []
        price = prices.get(asset)
        if not price or price <= 0:
            return RiskResult("HOLD", None, 0.0, [f"prix indisponible pour {asset}"])

        limits: list[tuple[float, str]] = []
        cap = self.total_value(prices) * max_trade_fraction
        limits.append((cap, f"plafond {max_trade_fraction:.0%} du portefeuille ({cap:.4f} $)"))
        if action == "BUY":
            # Pas de levier : notional + frais <= cash.
            affordable = self.cash / (1 + fee_rate)
            limits.append((affordable, f"cash disponible ({affordable:.4f} $ hors frais)"))
        else:  # SELL
            held = self.position_value(asset, prices)
            limits.append((held, f"position détenue ({held:.4f} $) — pas de vente à découvert"))

        allowed = amount_usd
        for limit, label in limits:
            if allowed > limit + EPS:
                if on_violation == "reject":
                    return RiskResult("HOLD", None, 0.0, [f"rejeté : dépasse {label}"])
                notes.append(f"réduit de {allowed:.4f} $ à {limit:.4f} $ : {label}")
                allowed = limit
        allowed = max(0.0, allowed)
        if allowed < min_trade_usd:
            notes.append(f"montant {allowed:.4f} $ < minimum {min_trade_usd} $ -> HOLD")
            return RiskResult("HOLD", None, 0.0, notes)
        return RiskResult(action, asset, allowed, notes)

    # ------------------------------------------------------------- exécution
    def buy(self, asset: str, notional: float, price: float, fee_rate: float,
            reason: str = "AGENT") -> Trade:
        fee = notional * fee_rate
        total = notional + fee
        if total > self.cash + EPS:
            raise ValueError(f"cash insuffisant : {total:.6f} > {self.cash:.6f}")
        qty = notional / price
        self.cash = max(0.0, self.cash - total)
        self.positions[asset] = self.positions.get(asset, 0.0) + qty
        self.cost_basis[asset] = self.cost_basis.get(asset, 0.0) + total
        return Trade("BUY", asset, qty, price, notional, fee, reason)

    def sell(self, asset: str, notional: float, price: float, fee_rate: float,
             reason: str = "AGENT") -> Trade:
        held = self.positions.get(asset, 0.0)
        qty = notional / price
        if qty > held * (1 + 1e-9) + EPS:
            raise ValueError(f"vente à découvert interdite : {qty} > {held} {asset}")
        qty = min(qty, held)
        notional = qty * price
        fee = notional * fee_rate
        # Le coût de revient diminue au prorata de la quantité vendue.
        if held > 0:
            self.cost_basis[asset] = self.cost_basis.get(asset, 0.0) * (1 - qty / held)
        remaining = held - qty
        if remaining * price < EPS:   # on nettoie les poussières
            self.positions.pop(asset, None)
            self.cost_basis.pop(asset, None)
        else:
            self.positions[asset] = remaining
        self.cash += notional - fee
        return Trade("SELL", asset, qty, price, notional, fee, reason)

    def execute(self, decision: RiskResult, prices: dict[str, float], fee_rate: float) -> Trade | None:
        if decision.action == "BUY":
            return self.buy(decision.asset, decision.amount_usd, prices[decision.asset], fee_rate)
        if decision.action == "SELL":
            return self.sell(decision.asset, decision.amount_usd, prices[decision.asset], fee_rate)
        return None

    # ---------------------------------------------------------------- survie
    def pay_costs(self, amount: float, prices: dict[str, float], fee_rate: float) -> tuple[float, list[Trade]]:
        """Déduit les coûts de survie (API + loyer).

        Si le cash ne suffit pas, des positions sont liquidées (frais compris)
        en commençant par la plus grosse. Retourne (montant impayé, ventes forcées).
        Le cash ne devient jamais négatif.
        """
        trades: list[Trade] = []
        if amount <= 0:
            return 0.0, trades
        if self.cash + EPS < amount:
            shortfall = amount - self.cash
            for asset in sorted(self.positions, key=lambda a: -self.position_value(a, prices)):
                price = prices.get(asset)
                if not price:
                    continue
                need = shortfall / (1 - fee_rate)   # notional brut nécessaire
                notional = min(need, self.position_value(asset, prices))
                t = self.sell(asset, notional, price, fee_rate, reason="LIQUIDATION")
                trades.append(t)
                shortfall -= t.notional - t.fee
                if shortfall <= EPS:
                    break
        paid = min(amount, self.cash)
        self.cash = max(0.0, self.cash - paid)
        unpaid = amount - paid
        # Un résidu d'arrondi (~1e-17 $) n'est pas une dette : seul un vrai impayé compte.
        return (unpaid if unpaid > EPS else 0.0), trades

    def is_dead(self, prices: dict[str, float], threshold: float = 0.0) -> bool:
        """Kill switch : valeur totale <= seuil."""
        return self.total_value(prices) <= threshold + EPS

    # ------------------------------------------------------------ sérialisation
    def to_dict(self) -> dict[str, Any]:
        return {"cash": self.cash, "positions": dict(self.positions),
                "cost_basis": dict(self.cost_basis)}

    @classmethod
    def from_dict(cls, d: dict[str, Any]) -> "Portfolio":
        return cls(cash=float(d["cash"]),
                   positions={k: float(v) for k, v in d.get("positions", {}).items()},
                   cost_basis={k: float(v) for k, v in d.get("cost_basis", {}).items()})
