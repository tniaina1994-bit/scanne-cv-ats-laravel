<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('nav.home') }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-5xl flex-col gap-10 p-6 lg:p-12">
            <header class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">ATS CV Analyzer</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('home.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed opacity-70">
                        {{ __('home.tagline') }}
                    </p>
                </div>
                <nav class="flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('scan.index') }}" class="rounded-md bg-[#1b1b18] px-4 py-2 font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black">
                        {{ __('home.scan_cta') }}
                    </a>
                    <a href="{{ route('extract.index') }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        {{ __('nav.extract') }}
                    </a>
                    <a href="{{ route('history.index') }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        {{ __('nav.history') }}
                    </a>
                    <a href="{{ route('compare.index') }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        {{ __('nav.compare') }}
                    </a>
                    <a href="{{ route('letter.index') }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        {{ __('nav.letter') }}
                    </a>
                    @auth
                        <a href="{{ route('admin.skills.index') }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                            {{ __('nav.skills_admin') }}
                        </a>
                        <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                                {{ __('nav.logout') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                            {{ __('nav.login') }}
                        </a>
                        <a href="{{ route('register') }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                            {{ __('nav.register') }}
                        </a>
                    @endauth
                    <a href="?lang={{ app()->getLocale() === 'fr' ? 'en' : 'fr' }}" class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        {{ app()->getLocale() === 'fr' ? 'EN' : 'FR' }}
                    </a>
                    <button type="button" data-theme-toggle class="rounded-md border border-[#e3e3e0] px-4 py-2 font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        <span data-theme-label>{{ __('nav.dark') }}</span>
                    </button>
                </nav>
            </header>

            <section class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('scan.index') }}" class="group rounded-xl border border-[#e3e3e0] bg-white p-6 transition hover:border-indigo-300 hover:shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-indigo-700">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-lg text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300" aria-hidden="true">◎</div>
                    <h2 class="text-lg font-semibold">{{ __('home.scan_card') }}</h2>
                    <p class="mt-1 text-sm leading-relaxed opacity-70">
                        {{ __('home.scan_card_desc') }}
                    </p>
                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600 underline underline-offset-4 group-hover:text-indigo-700 dark:text-indigo-400">
                        {{ __('home.open_scanner') }}
                    </span>
                </a>

                <a href="{{ route('extract.index') }}" class="group rounded-xl border border-[#e3e3e0] bg-white p-6 transition hover:border-indigo-300 hover:shadow-sm dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-indigo-700">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-lg text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300" aria-hidden="true">¶</div>
                    <h2 class="text-lg font-semibold">{{ __('home.extract_card') }}</h2>
                    <p class="mt-1 text-sm leading-relaxed opacity-70">
                        {{ __('home.extract_card_desc') }}
                    </p>
                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600 underline underline-offset-4 group-hover:text-indigo-700 dark:text-indigo-400">
                        {{ __('home.open_extract') }}
                    </span>
                </a>
            </section>

            <section class="rounded-xl border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('home.how_title') }}</h2>
                <ol class="mt-4 grid gap-4 sm:grid-cols-4">
                    <li class="rounded-lg bg-gray-50 p-4 dark:bg-[#0a0a0a]">
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">1. CV</span>
                        <p class="mt-1 text-sm">{{ __('home.how_1') }}</p>
                    </li>
                    <li class="rounded-lg bg-gray-50 p-4 dark:bg-[#0a0a0a]">
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">2. Offre</span>
                        <p class="mt-1 text-sm">{{ __('home.how_2') }}</p>
                    </li>
                    <li class="rounded-lg bg-gray-50 p-4 dark:bg-[#0a0a0a]">
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">3. Matching</span>
                        <p class="mt-1 text-sm">{{ __('home.how_3') }}</p>
                    </li>
                    <li class="rounded-lg bg-gray-50 p-4 dark:bg-[#0a0a0a]">
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">4. Résultat</span>
                        <p class="mt-1 text-sm">{{ __('home.how_4') }}</p>
                    </li>
                </ol>
            </section>

            <section class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-[#e3e3e0] bg-white p-4 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <strong class="block">PDF &amp; DOCX</strong>
                    <span class="opacity-70">{{ __('home.feat_ocr') }}</span>
                </div>
                <div class="rounded-lg border border-[#e3e3e0] bg-white p-4 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <strong class="block">{{ __('home.feat_score') }}</strong>
                    <span class="opacity-70">{{ __('home.feat_score_desc') }}</span>
                </div>
                <div class="rounded-lg border border-[#e3e3e0] bg-white p-4 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <strong class="block">API JSON</strong>
                    <span class="opacity-70"><code class="text-xs">POST /api/scan</code> multipart</span>
                </div>
            </section>

            <footer class="border-t border-[#e3e3e0] pt-4 text-xs opacity-50 dark:border-[#3E3E3A]">
                {{ __('home.footer') }} · Laravel {{ app()->version() }}
            </footer>
        </main>
    </body>
</html>
