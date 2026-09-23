<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Accueil — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-5xl flex-col gap-10 p-6 lg:p-12">
            <header class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">ATS CV Analyzer</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight sm:text-4xl">Scanne-CV-ATS</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed opacity-70">
                        Analysez un CV face à une offre d'emploi : score de compatibilité, compétences matchées
                        ou manquantes, contrôles qualité ATS et recommandations.
                    </p>
                </div>
                <nav class="flex gap-3 text-sm">
                    <a href="{{ route('scan.index') }}" class="rounded-md bg-[#1b1b18] px-4 py-2 font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black">
                        Scanner un CV
                    </a>
                    <a href="{{ route('extract.index') }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        Test extraction
                    </a>
                </nav>
            </header>

            <section class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('scan.index') }}" class="group rounded-xl border border-[#e3e3e0] bg-white p-6 transition hover:border-indigo-300 hover:shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-indigo-700">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-lg text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300" aria-hidden="true">◎</div>
                    <h2 class="text-lg font-semibold">Analyser un CV</h2>
                    <p class="mt-1 text-sm leading-relaxed opacity-70">
                        Téléversez un PDF ou DOCX, collez l'offre d'emploi (modèles inclus) et obtenez le score ATS
                        avec le détail des compétences et des vérifications.
                    </p>
                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600 underline underline-offset-4 group-hover:text-indigo-700 dark:text-indigo-400">
                        Ouvrir le scanner →
                    </span>
                </a>

                <a href="{{ route('extract.index') }}" class="group rounded-xl border border-[#e3e3e0] bg-white p-6 transition hover:border-indigo-300 hover:shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-indigo-700">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-lg text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300" aria-hidden="true">¶</div>
                    <h2 class="text-lg font-semibold">Test extraction de texte</h2>
                    <p class="mt-1 text-sm leading-relaxed opacity-70">
                        Vérifiez l'extraction seule : PDF texte (smalot), DOCX (unzip + XML) ou PDF scanné via
                        OCR Tesseract <code class="text-xs">fra+eng</code>.
                    </p>
                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600 underline underline-offset-4 group-hover:text-indigo-700 dark:text-indigo-400">
                        Ouvrir l'extraction →
                    </span>
                </a>
            </section>

            <section class="rounded-xl border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="text-sm font-semibold uppercase tracking-wide opacity-70">Comment ça marche</h2>
                <ol class="mt-4 grid gap-4 sm:grid-cols-4">
                    <li class="rounded-lg bg-gray-50 p-4 dark:bg-[#0a0a0a]">
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">1. CV</span>
                        <p class="mt-1 text-sm">Déposez le fichier (.pdf / .docx, 10 Mo max).</p>
                    </li>
                    <li class="rounded-lg bg-gray-50 p-4 dark:bg-[#0a0a0a]">
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">2. Offre</span>
                        <p class="mt-1 text-sm">Collez le poste (modèles Support IT, Dev, DevOps, Data).</p>
                    </li>
                    <li class="rounded-lg bg-gray-50 p-4 dark:bg-[#0a0a0a]">
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">3. Matching</span>
                        <p class="mt-1 text-sm">Exact → synonyme → lié → partiel → sémantique (TF-IDF).</p>
                    </li>
                    <li class="rounded-lg bg-gray-50 p-4 dark:bg-[#0a0a0a]">
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">4. Résultat</span>
                        <p class="mt-1 text-sm">Score global, checks ATS, infos perso, reco d'amélioration.</p>
                    </li>
                </ol>
            </section>

            <section class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-[#e3e3e0] bg-white p-4 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <strong class="block">PDF &amp; DOCX</strong>
                    <span class="opacity-70">OCR de secours pour les PDF scannés</span>
                </div>
                <div class="rounded-lg border border-[#e3e3e0] bg-white p-4 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <strong class="block">Score pondéré</strong>
                    <span class="opacity-70">Compétences 50% · XP 20% · Formation 10% · ATS 20%</span>
                </div>
                <div class="rounded-lg border border-[#e3e3e0] bg-white p-4 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <strong class="block">API JSON</strong>
                    <span class="opacity-70"><code class="text-xs">POST /api/scan</code> multipart</span>
                </div>
            </section>

            <footer class="border-t border-[#e3e3e0] pt-4 text-xs opacity-50 dark:border-[#3E3E3A]">
                Port Laravel de ProjetATS · Laravel {{ app()->version() }}
            </footer>
        </main>
    </body>
</html>
