# AfriFlow AI

**Le copilote business intelligent pour les PME africaines.**

AfriFlow AI centralise ventes, clients, dépenses et trésorerie d'une petite
entreprise, et permet d'interroger ces données en langage naturel grâce à un
assistant IA branché directement sur la base de données de l'entreprise.

> ⚠️ Projet en développement actif — voir la [Roadmap](#roadmap) pour l'état d'avancement.

---

## Sommaire

- [Le problème](#le-problème)
- [La solution](#la-solution)
- [Fonctionnalités](#fonctionnalités)
- [Le copilote IA](#le-copilote-ia)
- [Architecture](#architecture)
- [Stack technique](#stack-technique)
- [Démarrage rapide](#démarrage-rapide)
- [API](#api)
- [Sécurité](#sécurité)
- [Roadmap](#roadmap)
- [Licence](#licence)

---

## Le problème

La majorité des PME africaines gèrent encore leurs ventes, leurs clients et
leur trésorerie sur papier, sur WhatsApp ou sur des tableurs Excel dispersés.
Résultat : aucune visibilité en temps réel sur la santé réelle de l'activité,
et des décisions prises à l'aveugle (stock, prix, relance clients, dépenses).

Les logiciels de gestion existants sont soit trop chers, soit pensés pour des
marchés occidentaux (devises, fiscalité, méthodes de paiement) sans notion de
FCFA ni de mobile money.

## La solution

AfriFlow AI est un SaaS de gestion pensé pour ce contexte :

- suivi des ventes, clients, produits et dépenses en FCFA ;
- tableau de bord en temps réel (chiffre d'affaires, bénéfices, créances) ;
- un assistant IA capable de répondre en langage naturel à des questions sur
  les données réelles de l'entreprise (pas un chatbot générique) :

```text
"Combien ai-je gagné cette semaine ?"
"Quels produits se vendent le mieux ?"
"Pourquoi mon chiffre d'affaires baisse ?"
"Quels clients n'ont pas encore payé ?"
"Donne-moi 3 recommandations pour augmenter mes ventes."
```

## Fonctionnalités

- **Tableau de bord** — CA du jour/semaine/mois, bénéfice estimé, dépenses,
  créances, top produits, évolution du CA.
- **Ventes** — création de vente multi-produits, remises, mode de paiement
  (espèces, mobile money, autre), historique.
- **Clients** — fiche client, historique d'achats, montant dépensé, dettes.
- **Dépenses** — catégorisation (transport, salaire, stock, loyer, marketing,
  fournisseurs...), visualisation par catégorie et période.
- **Copilote IA** — questions en langage naturel sur les données réelles de
  l'entreprise, et rapport d'analyse automatique de l'activité.

## Le copilote IA

Le copilote ne fait pas que "discuter" : il traduit une question en langage
naturel en requête structurée sur les données réelles de l'entreprise, puis
formule une réponse à partir du résultat.

```text
Question utilisateur
       │
       ▼
Détection d'intention
       │
       ▼
Appel d'un outil métier (ex: get_sales_statistics)
       │
       ▼
Requête PostgreSQL
       │
       ▼
Résultat structuré
       │
       ▼
Réponse en langage naturel (LLM)
```

Fonction phare : **« Analyser mon activité »**, qui génère un rapport
automatique (performance, produit phare, points d'attention, recommandations,
objectif suggéré pour le mois suivant).

Implémentation concrète (backend, via l'[API Claude](https://console.anthropic.com)
d'Anthropic, modèle `claude-opus-5`) :

- `POST /api/copilot/ask` — boucle de function calling : le modèle choisit
  parmi 5 outils métier (`get_sales_summary`, `get_top_products`,
  `get_top_customers`, `get_customers_with_unpaid_balance`,
  `get_expenses_by_category`), chacun exécutant une requête Doctrine réelle
  scopée à l'entreprise de l'utilisateur, avant de formuler sa réponse.
- `POST /api/copilot/analyze` — agrège les statistiques du mois en cours puis
  demande au modèle de rédiger le rapport structuré en un seul appel.

Nécessite une clé `ANTHROPIC_API_KEY` (voir [Démarrage rapide](#démarrage-rapide)).
Sans clé configurée, ces deux endpoints renvoient une erreur — le reste de
l'application fonctionne normalement.

## Architecture

```text
┌──────────────────┐        REST / JSON         ┌──────────────────┐
│  Angular 22       │ ─────────────────────────▶ │  Symfony 8 (API)  │
│  frontend/         │ ◀───────────────────────── │  backend/          │
└──────────────────┘                             └─────────┬────────┘
                                                            │
                                              ┌─────────────┼─────────────┐
                                              ▼                           ▼
                                     ┌──────────────────┐        ┌──────────────────┐
                                     │   PostgreSQL 16   │        │   Couche IA        │
                                     │                    │        │   LLM + outils      │
                                     └──────────────────┘        │   métier            │
                                                                   └──────────────────┘
```

- `frontend/` — application Angular (dashboard, ventes, clients, dépenses).
- `backend/` — API Symfony (API Platform), authentification JWT, logique
  métier, exposition d'outils structurés pour la couche IA.
- `infrastructure/` — Dockerfiles de build pour le backend et le frontend.
- `docker-compose.yml` — environnement de développement complet (API,
  frontend, PostgreSQL, Adminer).

## Stack technique

| Composant       | Techno                                   |
| ---------------- | ----------------------------------------- |
| Frontend          | Angular 22, TypeScript, SCSS              |
| Backend           | Symfony 8, PHP 8.4, API Platform          |
| Base de données   | PostgreSQL 16, Doctrine ORM               |
| Authentification  | JWT (LexikJWTAuthenticationBundle)        |
| IA                | API Claude (Anthropic), function calling  |
| Infrastructure    | Docker, Docker Compose                    |
| CI                | GitHub Actions                            |

## Démarrage rapide

### Prérequis

- Docker et Docker Compose
- (optionnel, hors Docker) PHP 8.4+, Composer, Node.js 22+

### Avec Docker (recommandé)

```bash
git clone https://github.com/<ton-compte>/afriflow-ai.git
cd afriflow-ai
cp backend/.env backend/.env.local   # puis ajuster les secrets si besoin
echo "ANTHROPIC_API_KEY=sk-ant-..." >> backend/.env.local   # requis pour le copilote IA
docker compose up -d
```

- Frontend : http://localhost:4200
- API : http://localhost:8000
- Documentation API (OpenAPI) : http://localhost:8000/api
- Adminer (DB) : http://localhost:8080

### Sans Docker

```bash
# Backend
cd backend
composer install
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfony serve -d

# Frontend
cd frontend
npm install
npm start
```

## API

L'API suit une architecture REST exposée par API Platform, avec documentation
OpenAPI générée automatiquement et consultable sur `/api`.

Ressources principales : `Customer`, `Product`, `Sale` (avec ses `SaleItem`),
`Expense`, `Payment`. Chaque entreprise ne voit que ses propres données
(isolation multi-tenant appliquée au niveau de la couche Doctrine, voir
[Sécurité](#sécurité)).

Une couche de statistiques (`/api/stats/*`) expose en plus, pour une période
donnée (`?from=&to=`) :

| Endpoint                     | Contenu                                              |
| ----------------------------- | ----------------------------------------------------- |
| `GET /api/stats/summary`      | CA, bénéfice estimé, dépenses, créances, nb de ventes |
| `GET /api/stats/top-products` | Produits les plus vendus (quantité + CA généré)       |
| `GET /api/stats/top-customers`| Meilleurs clients (dépensé + impayé)                  |
| `GET /api/stats/revenue-series`| Évolution du CA jour par jour, pour le graphique     |

### Authentification

```bash
# 1. Créer un compte (entreprise + premier utilisateur admin)
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"companyName":"Boutique Awa","fullName":"Awa Diallo","email":"awa@boutique-awa.sn","password":"password123"}'

# 2. Se connecter pour obtenir un token JWT
curl -X POST http://localhost:8000/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"email":"awa@boutique-awa.sn","password":"password123"}'

# 3. Appeler l'API avec le token
curl http://localhost:8000/api/me -H "Authorization: Bearer <token>"
```

### Enregistrer une vente

```bash
curl -X POST http://localhost:8000/api/sales \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "customerId": 1,
    "paymentMethod": "mobile_money",
    "items": [{"productId": 1, "quantity": 2}]
  }'
```

La réponse inclut `totalAmount`, `amountPaid`, `balanceDue` et `status`
(`unpaid` / `partially_paid` / `paid`), recalculés automatiquement à chaque
paiement enregistré via `POST /api/payments`.

## Sécurité

- Authentification par JWT, un utilisateur appartenant à une seule entreprise
  (isolation des données multi-tenant au niveau applicatif).
- Validation des entrées via le composant Validator de Symfony.
- Secrets (clés JWT, `APP_SECRET`, identifiants base de données) exclus du
  dépôt via `.gitignore` et gérés par variables d'environnement.

## Roadmap

- [x] Jour 1 — Architecture : scaffolding Angular/Symfony, Docker, CI, README
- [x] Jour 2 — Backend : entités, migrations, API REST, authentification JWT,
      isolation multi-tenant, tests fonctionnels
- [x] Jour 3 — Frontend : auth, dashboard, ventes (avec paiements), clients,
      produits, dépenses — logiciel utilisable de bout en bout
- [x] Jour 4 — Business intelligence : endpoints d'agrégation (`/api/stats/*`),
      sélecteur de période, graphique d'évolution du CA, top produits/clients
- [x] Jour 5 — Copilote IA : chat avec function calling (API Claude) sur les
      données métier réelles, rapport d'activité automatique
- [ ] Jour 6 — Audit qualité : sécurité, UX, accessibilité, tests
- [ ] Jour 7 — Déploiement : environnement de démo, documentation finale

## Licence

[MIT](LICENSE)
