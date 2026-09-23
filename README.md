# Scanne-CV-ATS

Analyseur de CV / ATS en **Laravel 13** (Blade + Tailwind 4 + Vite 8). Port fullstack de **ProjetATS** : extraction de texte (PDF/DOCX + OCR), matching de compétences, scoring ATS, recommandations.

## Fonctionnalités

- **`/scan`** — upload CV (`.pdf` / `.docx`, ≤ 10 Mo) + offre d’emploi (éditeur enrichi, 4 modèles) → score, checks ATS, infos perso, compétences matchées/manquantes, recommandations
- **`POST /api/scan`** — même analyse en JSON
- **`/extract`** — extraction seule du texte (aperçu, CV d’exemple)
- OCR **Tesseract** (`fra+eng`) en secours pour les PDF scannés
- Extraction DOCX via CLI `unzip` (pas d’`ext-zip` requis)

## Prérequis

| Outil | Rôle |
| --- | --- |
| PHP **≥ 8.3** (+ `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`) | application |
| Composer | dépendances PHP |
| Node.js + npm | assets (Tailwind / Vite) |
| SQLite | base par défaut (`database/database.sqlite`) |
| `unzip` | lecture DOCX |
| `pdftoppm` + `tesseract` | OCR des PDF scannés (optionnel mais recommandé) |

> Sur Debian/Ubuntu (si droits root) : `sudo apt install unzip poppler-utils tesseract-ocr tesseract-ocr-fra tesseract-ocr-eng`

## Installation

### Commande recommandée

```bash
composer install          # dépendances PHP (nécessaire pour lancer Artisan)
php artisan install:app   # .env, APP_KEY, SQLite, migrations, npm, build
```

`install:app` exécute dans l’ordre :

1. Vérification des prérequis (PHP, extensions, binaires)
2. Création de `.env` depuis `.env.example` s’il manque
3. Génération de `APP_KEY` si absente
4. Création de `database/database.sqlite` si absente
5. `composer install` si `vendor/` manque (`--skip-composer` pour ignorer)
6. `php artisan migrate --force` (`--skip-migrate` pour ignorer)
7. `npm install` + `npm run build` (`--skip-npm` pour ignorer)
8. Nettoyage des caches locaux (config / views / routes)

Options :

```bash
php artisan install:app --check     # vérifie les prérequis sans rien modifier
php artisan install:app --skip-npm  # sans front
php artisan install:app --skip-composer --skip-migrate --skip-npm  # config seule
```

### Variante Composer

```bash
composer setup
```

Équivalent à `composer install` puis `php artisan install:app --skip-composer`.

### Vérification rapide

```bash
php artisan about
php artisan test --compact
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/up   # 200
```

## Démarrage

```bash
composer dev        # serve + Vite + … (recommandé en développement)
# ou
php artisan serve    # http://127.0.0.1:8000
npm run dev          # assets en mode watch (autre terminal)
```

Puis ouvrir `/`, `/scan` ou `/extract`.

## Tests & qualité

```bash
php artisan test --compact           # suite complète
php artisan test --filter=ScanTest   # un fichier / nom
vendor/bin/pint --format agent       # formatage PHP (après toute édition)
npm run build                        # si le manifest Vite manque / UI périmée
```

## Configuration utile

Fichier `.env` (voir `.env.example`) :

| Clé | Défaut projet |
| --- | --- |
| `APP_NAME` | `Scanne-CV-ATS` |
| `DB_CONNECTION` | `sqlite` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |

Les sessions / cache / queues utilisent la base : **lancez les migrations** avant de servir en HTTP.

## API

`POST /api/scan` — `multipart/form-data` :

- `file` : PDF ou DOCX (≤ 10 Mo)
- `job_offer` : texte de l’offre

Réponse JSON : score, compétences matchées/manquantes, checks ATS, infos personnelles, recommandations.

## Projet de référence

Logique métier portée depuis **ProjetATS** (Angular + FastAPI) :

- `ats-cv-analyzer/backend/app/api/routes/scan.py`
- `ats-cv-analyzer/data/synonyms.json` → `resources/data/synonyms.json`

## Licence

MIT (squelette Laravel) — voir `composer.json`.
