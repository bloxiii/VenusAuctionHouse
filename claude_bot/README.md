# CLAUDE BOT / SURVIVAL MODE

Un agent de trading **autonome en paper trading** : une IA lâchée avec 50 $ *fictifs*
et une seule règle, **survivre**. Toutes les 10 minutes, Claude reçoit des prix crypto
réels (BTC, ETH, SOL), décide d'acheter, de vendre ou d'attendre, et chaque appel API
lui coûte une partie de son solde. Si la valeur du portefeuille tombe à 0 $, il meurt.

> ⚠️ **Projet d'apprentissage. Aucun argent réel, aucune connexion à un compte de
> trading, jamais.** Les ordres sont simulés localement. Seuls les *prix* sont réels.

---

## Architecture

```
claude_bot/
├── config.yaml          # toutes les règles du jeu (commentées)
├── run_agent.py         # lance l'agent (boucle)
├── run_dashboard.py     # lance le tableau de bord (http://localhost:8000)
├── agent/
│   ├── config.py        # chargement + validation de config.yaml, loyer au prorata
│   ├── prompts.py       # prompt système + message de chaque cycle
│   ├── llm.py           # appel Claude (SDK anthropic) + calcul du coût réel
│   ├── validation.py    # validation stricte du JSON de décision
│   ├── dummy.py         # stratégie factice pour --dry-run
│   └── loop.py          # déroulé d'un cycle, kill switch, boucle principale
├── market/binance.py    # prix + bougies 1 h (Binance, secours CoinGecko), retries
├── portfolio/
│   ├── portfolio.py     # cash, positions, frais, limites de risque, coûts de survie
│   └── ledger.py        # SQLite (ledger.db) : cycles, transactions, état de l'agent
├── dashboard/           # app Flask + page « terminal » (Chart.js, échelle log)
└── tests/               # pytest
```

**Principe clé : le modèle propose, le code dispose.** Chaque cycle :

1. récupère les prix (si impossible → cycle `SKIPPED`, aucune décision à l'aveugle) ;
2. demande une décision JSON à Claude ;
3. déduit les coûts de survie (coût réel de l'appel + loyer), en vendant des
   positions si le cash ne suffit pas — le cash n'est jamais négatif ;
4. valide la réponse (sinon `HOLD` forcé, statut `INVALID`) ;
5. applique les limites de risque (pas de short, pas de levier, ≤ 50 % du
   portefeuille par ordre, montant minimal) puis simule l'ordre avec 0,1 % de frais ;
6. kill switch : valeur ≤ 0 $ → `DEAD`, définitivement ;
7. écrit le tout dans `ledger.db` en une seule transaction SQL.

Les données venues de l'extérieur (prix) sont converties en nombres et envoyées entre
balises `<donnees>` ; le prompt système précise que ce sont des données, jamais des
instructions.

---

## Installation

Python 3.10 ou plus récent.

```bash
cd claude_bot
python -m venv .venv
source .venv/bin/activate          # Windows : .venv\Scripts\activate
pip install -r requirements.txt
```

### Obtenir une clé API Anthropic

1. Créez un compte sur **https://console.anthropic.com**.
2. Dans *Settings → Billing*, ajoutez quelques dollars de crédit (5 $ suffisent largement).
3. Dans *Settings → API Keys*, cliquez **Create Key** et copiez la clé (`sk-ant-...`).
4. Exportez-la dans votre terminal (elle n'est **jamais** écrite dans le code) :

```bash
export ANTHROPIC_API_KEY="sk-ant-..."         # macOS / Linux
setx ANTHROPIC_API_KEY "sk-ant-..."           # Windows (puis rouvrir le terminal)
```

Conseil : dans la console, fixez une **limite de dépense mensuelle** basse.

---

## Lancement

Deux terminaux :

```bash
# Terminal 1 — l'agent (48 h, un cycle toutes les 10 min)
python run_agent.py

# Terminal 2 — le tableau de bord
python run_dashboard.py
# puis ouvrez http://localhost:8000
```

Le dashboard se rafraîchit toutes les 5 s : statut ALIVE / DEAD / SURVIVED, valeur vs
50 $, P&L en $ et %, coûts cumulés (API + loyer + frais), courbe de la valeur en échelle
logarithmique, positions ouvertes, dernières décisions avec le raisonnement de l'agent
et les éventuelles corrections appliquées par les limites de risque, compteur `42 / 288`.

### Arrêt et reprise

`Ctrl+C` arrête l'agent proprement. Relancez `python run_agent.py` : il reprend au cycle
suivant avec le même portefeuille (même après un arrêt brutal, puisque chaque cycle est
écrit de façon atomique). Un agent `DEAD` ou ayant terminé ses 288 cycles ne repart pas ;
pour une nouvelle partie :

```bash
python run_agent.py --reset
```

---

## Modes de test

| Option | Effet |
|---|---|
| `--dry-run` | Pas d'appel à Claude : stratégie factice (`random` ou `hold`, voir `config.yaml`). Aucun crédit dépensé ; un coût API **estimé** est déduit pour tester la mécanique de survie (désactivable : `dry_run.simulate_api_cost: false`). Pas besoin de clé API. |
| `--fast` | Cycles de 3 s au lieu de 10 min (`game.fast_interval_seconds`). Le loyer reste calculé sur 10 min. |
| `--max-cycles N` | Remplace `game.max_cycles` (ex. un test de 20 cycles). |
| `--db chemin.db` | Utilise un autre ledger (pratique pour ne pas mélanger tests et vraie partie). |
| `--simulated-market` | Prix fictifs (marche aléatoire) au lieu des API publiques — pour tester hors ligne ou quand le réseau bloque Binance/CoinGecko. |
| `--reset` | Efface le ledger et repart de 50 $. |

Exemples :

```bash
python run_agent.py --dry-run --fast --max-cycles 30 --db test.db --reset
python run_dashboard.py --db test.db
```

Tests unitaires (frais, limites de risque, validation JSON, kill switch, reprise) :

```bash
python -m pytest -v
```

---

## Coût API estimé

Modèle par défaut : **Claude Haiku 5.5** (`claude-haiku-5-5`) — 0,10 $ / million de
tokens en entrée, 0,50 $ / million en sortie (tarifs dans `config.yaml`, à mettre à jour
si Anthropic les change).

Par cycle, l'agent envoie ≈ 2 000 à 3 000 tokens (règles, portefeuille, 24 bougies × 3
actifs, 10 dernières décisions) et reçoit ≈ 100 à 500 tokens (JSON + réflexion interne,
limitée par `model.effort: low`) :

| | Par cycle | Par jour (144 cycles) | Partie complète (48 h) |
|---|---|---|---|
| Estimation basse | ~0,0003 $ | ~0,04 $ | ~0,08 $ |
| Estimation typique | ~0,0005 $ | ~0,07 $ | ~0,15 $ |
| Estimation haute | ~0,001 $ | ~0,15 $ | ~0,30 $ |

Ce sont des estimations : le coût **réel** de chaque appel (tokens renvoyés par l'API ×
tarifs) est enregistré dans le ledger et affiché sur le dashboard. Avec 50 $ de départ,
les coûts API seuls ne tueront pas l'agent : ce sont ses décisions de trading (et les
frais) qui décideront de sa survie. Pour durcir le jeu, augmentez
`survival.rent_per_month_usd` ou passez à un modèle plus cher (`model.name` + `pricing`).

---

## Configuration (extrait)

Tout se règle dans `config.yaml` : solde initial, intervalle, nombre de cycles, actifs,
frais, loyer, seuil du kill switch, limites de risque (`clamp` = réduire un ordre trop
gros, `reject` = le refuser), modèle et tarifs, sources de marché, taille de la mémoire
(10 décisions), port du dashboard.

## Dépannage

- **Tous les cycles sont `SKIPPED`** : les API de marché sont injoignables (pare-feu,
  pays bloqué par `api.binance.com`…). Le programme essaie aussi
  `data-api.binance.vision` puis CoinGecko. Testez avec `--simulated-market`.
- **`ANTHROPIC_API_KEY absente`** : exportez la clé dans le *même* terminal, ou utilisez
  `--dry-run`.
- **Statut `LLM_ERROR`** : erreur réseau ou quota côté API ; le cycle applique un HOLD
  et l'agent continue.
- **Graphique vide** : le dashboard charge Chart.js depuis cdnjs, il faut un accès internet.
