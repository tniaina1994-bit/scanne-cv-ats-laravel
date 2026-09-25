# AGENTS.md — Scanne-CV-ATS-Laravel

Laravel fullstack port of **ProjetATS** (ATS CV analyzer). Text extraction + core ATS analysis are ported; UI is Blade (no Angular).

## Reference implementation (feature source of truth)

`/home/mandry/Documents/ProjetWeb/ProjetATS` (Angular + FastAPI) — treat as read-only reference:

- `ats-cv-analyzer/backend/app/api/routes/scan.py` — ~600-line monolith: upload, PDF/DOCX extract, OCR fallback, skill matching, scoring. Port logic from here, not from the planned folder layout in its `AGENTS.md`.
- `ats-cv-analyzer/data/synonyms.json` — 96 canonical skills → synonyms (copied to `resources/data/synonyms.json`).
- `ats-cv-analyzer/data/*.{pdf,docx}` — sample CVs for manual checks.
- `ATS_CV_Analyzer_Angular_FastAPI_Plan_Cahier_des_Charges.md` — product spec.

Behaviors preserved when porting:

- `POST /api/scan`: multipart `file` (`.pdf`/`.docx` only, ≤10MB) + `job_offer` (markdown). Validation errors → 422 JSON; extraction failures → 400 `{error}`.
- Match order: exact → synonym → related → partial → semantic (TF-IDF cosine, threshold 0.15).
- Score: `skills*0.5 + experience*0.2 + education*0.1 + ats_quality*0.2`.
- Scanned PDFs: OCR fallback with Tesseract `fra+eng` (Tesseract 5.5 is on this machine).
- **Tesseract hangs without `OMP_THREAD_LIMIT=1`** on this box (OpenMP thread thrashing) — always pass `OMP_THREAD_LIMIT=1` / `OMP_NUM_THREADS=1` when spawning it (already done in `DocumentTextExtractor`).

## Stack

- Laravel 13 / PHP 8.5 / SQLite (default) / Blade + Tailwind 4 + Vite 8
- PHPUnit (sqlite `:memory:`), Laravel Pint, Laravel Boost (MCP wired in `opencode.json`)
- Text extraction: `smalot/pdfparser` (PDF), `unzip` CLI + Word XML (DOCX), `pdftoppm` + `tesseract fra+eng` (OCR)
- **No `ext-zip` / `ext-gd` / Imagick** on this machine (no sudo) — do not add PHPWord or image libs that require them; DOCX must stay on the unzip CLI path (`App\Services\DocumentTextExtractor`)

## ATS analysis (ported)

- `GET/POST /scan` — Blade UI: upload CV + job offer rich editor (4 templates: Support IT / Développeur / DevOps / Data) → score, ATS checks, personal info, matched/missing skills with match-type badges, recommendations, reformulation suggestions. PRG + session flash. Drag & drop, progress bar, print CSS, dark-mode toggle. PDF without native text → `OcrExtractJob` (database queue).
- `POST /api/scan` — JSON API (same contract as ProjetATS `ScanResult`) + `reformulation_suggestions`. Throttled 10/min/IP (`api-scan`). Results cached 1h by `sha256(file+job_offer)`.
- `GET/POST /compare` — compare 2 CVs against one offer (scores, missing-skill delta, suggestions).
- `GET/POST /letter` — cover letter vs offer (`CoverLetterAnalyzer`): skills/length/structure/alignment sub-scores + FR suggestions (F-10).
- `GET/POST /admin/skills` — auth-guarded CRUD for custom skills (`skills` table overlays `synonyms.json` at runtime) (F-14).
- Auth: `GET/POST /login` (throttle 5/min), `GET/POST /register`, `POST /logout`. Named routes `login`/`register` (required by Laravel `auth` middleware redirect).
- History: routes `/history*` + `/share/*` active (`HistoryController`); nav links present in home/scan/compare/extract/history views. CSV export + signed share links.
- i18n chrome: `lang/{fr,en}/*.php`, `SetLocale` middleware, `?lang=fr|en` persists session. Default `APP_LOCALE=fr`.
- Services: `SkillLibrary`, `CvAnalyzer`, `TfIdfSemanticMatcher`, `PersonalInfoExtractor`, `DocumentTextExtractor` (`extract($path,$ext,$allowOcr=true)`), `ReformulationSuggester`, `CvSectionDetector`, `JobOfferRequirements`, `CoverLetterAnalyzer`.
- Advanced analysis (F-10…F-17): `scores.semantic` (weight default 0 via `ATS_WEIGHT_SEMANTIC`), sections map + `section_keys`, `experience_required`/`experience_gap` recommendations, FR/MG TF-IDF stop-words + `MULTI_LANG_ALIASES`, configurable weights in `config/scan.php` (`ATS_WEIGHT_*`), enriched reformulation templates.
- CI: `.github/workflows/ci.yml` (PHP 8.5, pint, tests, npm build).

## Text extraction test UI

- `GET/POST /extract` — upload `.pdf`/`.docx` (≤10MB) or load sample CVs from ProjetATS `data/`
- Service: `app/Services/DocumentTextExtractor.php`

## Commands

```bash
composer setup            # full from-scratch: install, .env, key, migrate, npm, build
composer dev               # php artisan dev (serve + vite + …)
php artisan test --compact # tests (single: php artisan test --filter=Name)
vendor/bin/pint --format agent  # required after any PHP edit (--dirty needs git; this repo may not be a git repo)
npm run build              # if Vite manifest missing / UI stale
```

## Gotchas

- Default DB file: `database/database.sqlite`; session, cache, and queue all use database tables — run migrations before relying on them.
- `CLAUDE.md` is a byte-for-byte copy of this file (Boost). Keep them in sync when editing (`cp AGENTS.md CLAUDE.md`).
- Boost skills live in `.agents/skills/`; `.ai/rules` does not exist yet (skip rule lookup until it does).
- Health route: `GET /up` (see `bootstrap/app.php`).
- Sample CV paths in tests/controllers point at ProjetATS `data/` (absolute path) — tests skip if missing.
- Locale keys use per-group files (`lang/fr/scan.php` + `__('scan.title')`), not a flat `lang/fr.php`.
- OCR: **sync by default** in the web request (`config('scan.ocr_async') === false`); set `SCAN_OCR_ASYNC=true` to queue `OcrExtractJob` (then run `php artisan queue:work`). Pending flash links to history. Tests use `Queue::fake()` / `QUEUE_CONNECTION=sync`.
- Forms/UI strings use `lang/{fr,en}/{scan,compare,extract,history,home,nav,editor,letter,skills}.php` keys (`__('scan.*')` etc.).
- `phpunit.xml` sets `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `APP_ENV=testing`.
- Run Pint + full test suite **sequentially** (never parallel) — they race on `.env`/config.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
