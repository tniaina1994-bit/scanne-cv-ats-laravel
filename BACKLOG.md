# Backlog — Scanne-CV-ATS-Laravel

Fichier de suivi des fonctionnalités à ajouter / finaliser.
Priorités : **P0** (bloquant / suite logique), **P1** (haute valeur), **P2** (plus tard).

---

## En cours / à finaliser

| ID | Priorité | Fonctionnalité | Notes |
|----|----------|----------------|-------|
| — | — | — | — |

---

## Analyse ATS

| ID | Priorité | Fonctionnalité | Notes |
|----|----------|----------------|-------|
| F-10 | P1 | Lettre de motivation vs offre | Score + suggestions (même moteur que CV) — **Fait** |
| F-11 | P1 | Réécriture assistée du CV | Version « ATS-friendly » des expériences / reformulations — **Fait** |
| F-12 | P1 | Score sémantique en sous-score | `scores.semantic` + poids `ATS_WEIGHT_SEMANTIC` — **Fait** |
| F-13 | P1 | Détection de sections CV | `CvSectionDetector` (headers FR/EN/MG) — **Fait** |
| F-14 | P1 | Base de compétences éditable | CRUD `/admin/skills` (auth) + overlay DB — **Fait** |
| F-15 | P2 | Matching multi-langue FR/EN/MG | Stop-words FR/MG + alias `MULTI_LANG_ALIASES` — **Fait** |
| F-16 | P2 | Pondération du score configurable | `config/scan.php` + `ATS_WEIGHT_*` — **Fait** |
| F-17 | P2 | Niveau d’expérience requis | `experience_required` / `experience_gap` + reco — **Fait** |

---

## Exports & rapports

| ID | Priorité | Fonctionnalité | Notes |
|----|----------|----------------|-------|
| F-20 | P1 | Export PDF du rapport | Print CSS déjà là → Browsershot/dompdf (pas d’ext-gd Imagick) |
| F-21 | P2 | Export JSON / API versionné | Intégrations tierces |
| F-22 | P1 | Batch : N CVs vs 1 offre | Classement des candidats |

---

## Productivité

| ID | Priorité | Fonctionnalité | Notes |
|----|----------|----------------|-------|
| F-30 | P1 | Bibliothèque d’offres | CRUD offres réutilisables (rich editor) |
| F-31 | P1 | Bibliothèque de CVs | Re-scan sans re-upload |
| F-32 | P2 | Compare N CVs | Au-delà de 2 |
| F-33 | P2 | Diff 2 scores | Compétences avant/après reformulation |
| F-34 | P2 | Modèles d’offres custom | Au-delà des 4 prédéfinis |

---

## Comptes & sécurité

| ID | Priorité | Fonctionnalité | Notes |
|----|----------|----------------|-------|
| F-40 | P1 | Rôles admin/user | + soft-delete données |
| F-41 | P1 | Mot de passe oublié | Mail log en local |
| F-42 | P2 | 2FA optionnel | TOTP |
| F-43 | P1 | Sanctum pour `POST /api/scan` | API keys ( throttle actuel : IP only ) |
| F-44 | P1 | RGPD | Purge auto scans, consentement, export données |
| F-45 | P2 | Audit log | Scans / suppressions |

---

## IA locale (cahier Phase 8)

| ID | Priorité | Fonctionnalité | Notes |
|----|----------|----------------|-------|
| F-50 | P2 | Embeddings | Sentence Transformers / Ollama au-dessus du TF-IDF |
| F-51 | P2 | Explications de score | Langage naturel |
| F-52 | P2 | Reformulations générées | Au-delà des templates FR déterministes |

---

## UX / dev

| ID | Priorité | Fonctionnalité | Notes |
|----|----------|----------------|-------|
| F-60 | P2 | Dashboard stats | Scores moyens, top skills manquants |
| F-61 | P1 | Notifications fin OCR / erreurs | Flash + éventuellement mail |
| F-62 | P2 | PWA | Offline lecture d’offre |
| F-63 | P2 | Docker Compose | Laravel + DB + optional Ollama |
| F-64 | P2 | OpenAPI documenté | Swagger UI pour l’API |
| F-65 | P2 | Tests E2E | Dusk / Playwright |

---

## Ops

| ID | Priorité | Fonctionnalité | Notes |
|----|----------|----------------|-------|
| F-70 | P1 | Scheduler purge | `pending-ocr/`, cache, jobs failés |
| F-71 | P1 | Health checks étendus | tesseract, pdftoppm, unzip |
| F-72 | P2 | Backup SQLite | Automatisé |

---

## Ordre conseillé

1. ~~**F-01** historique réactivé~~ ✅  
2. **F-20** export PDF rapport  
3. **F-30 + F-31** bibliothèque offres / CVs  
4. **F-43** API Sanctum  
5. **F-50** embeddings / Ollama  

---

## Fait (récent)

- [x] F-10 Lettre de motivation (`/letter`, `CoverLetterAnalyzer`)
- [x] F-11 Réécritures enrichies (XP requise, formation, structure ATS)
- [x] F-12 Score sémantique `scores.semantic`
- [x] F-13 Détection de sections (`CvSectionDetector`)
- [x] F-14 CRUD compétences (`/admin/skills` + table `skills`)
- [x] F-15 Multi-langue (stop-words FR/MG + alias)
- [x] F-16 Pondérations configurables (`config/scan.php`)
- [x] F-17 Expérience requise / écart
- [x] F-01 Historique réactivé (routes `/history*` + `/share/*`, nav, tests sans skip)
- [x] F-02 UI i18n formulaires (scan / compare / extract / history / home / editor / auth)
- [x] F-03 OCR async UX (flash pending + hint historique ; sync par défaut, async via `SCAN_OCR_ASYNC=true`)
- [x] OCR synchrone par défaut (`SCAN_OCR_ASYNC`)
- [x] Rich editor offres partagé scan ↔ compare (`x-job-offer-editor`)
- [x] Historique masqué (routes commentées) — **remplacé par F-01**
- [x] Auth login/register/logout
- [x] Compare 2 CVs
- [x] API `/api/scan` + cache + throttle
- [x] CI GitHub Actions
