<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('letter.title') }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-4xl flex-col gap-6 p-6 lg:p-10">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ __('letter.title') }}</h1>
                    <p class="mt-1 text-sm opacity-70">{{ __('letter.subtitle') }}</p>
                </div>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('scan.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.scan') }}</a>
                    <a href="{{ route('history.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.history') }}</a>
                    <a href="?lang={{ app()->getLocale() === 'fr' ? 'en' : 'fr' }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ app()->getLocale() === 'fr' ? 'EN' : 'FR' }}</a>
                    <a href="{{ route('home') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.home') }}</a>
                    <button type="button" data-theme-toggle class="underline underline-offset-4 opacity-70 hover:opacity-100">
                        <span data-theme-label>{{ __('nav.dark') }}</span>
                    </button>
                </nav>
            </header>

            @if (isset($error))
                <div class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    {{ $error }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('letter.form_title') }}</h2>

                <form method="POST" action="{{ route('letter.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="job_offer" class="mb-1 block text-sm font-medium">{{ __('letter.job_offer') }}</label>
                        <textarea
                            id="job_offer"
                            name="job_offer"
                            rows="8"
                            required
                            class="w-full rounded-md border border-[#e3e3e0] bg-white p-3 font-mono text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                        >{{ old('job_offer', $jobOffer) }}</textarea>
                    </div>

                    <div>
                        <label for="letter" class="mb-1 block text-sm font-medium">{{ __('letter.letter_label') }}</label>
                        <textarea
                            id="letter"
                            name="letter"
                            rows="12"
                            minlength="80"
                            required
                            placeholder="{{ __('letter.letter_placeholder') }}"
                            class="w-full rounded-md border border-[#e3e3e0] bg-white p-3 text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                        >{{ old('letter', $letter) }}</textarea>
                    </div>

                    <input type="hidden" name="job_offer_html" id="job_offer_html" value="">

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <button type="reset" class="rounded-md border border-[#e3e3e0] px-5 py-2 text-sm font-medium opacity-80 hover:opacity-100 dark:border-[#3E3E3A]">
                            {{ __('letter.clear') }}
                        </button>
                        <button type="submit" class="rounded-md bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black">
                            {{ __('letter.submit') }}
                        </button>
                    </div>
                </form>
            </section>

            @isset($result)
                @php
                    $score = $result['global_score'];
                    $scoreClass = $score >= 70
                        ? 'bg-green-600 text-white'
                        : ($score >= 40 ? 'bg-amber-500 text-white' : 'bg-red-500 text-white');
                @endphp

                <section id="letter-results" class="flex flex-col gap-4">
                    <div class="print:hidden">
                        <button type="button" id="print-letter" class="rounded-md border border-[#e3e3e0] px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                            {{ __('letter.print') }}
                        </button>
                    </div>

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('letter.score') }}</h2>
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full text-2xl font-bold {{ $scoreClass }}">
                            {{ $score }}%
                        </div>
                        <p class="mt-3 text-sm opacity-70">{{ $result['word_count'] }} {{ __('letter.word_count') }}</p>
                    </div>

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('letter.scores') }}</h2>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($result['scores'] as $label => $value)
                                <div class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 dark:bg-[#0a0a0a]">
                                    <span class="text-sm opacity-70">
                                        @switch($label)
                                            @case('skills') {{ __('letter.score_skills') }} @break
                                            @case('length') {{ __('letter.score_length') }} @break
                                            @case('structure') {{ __('letter.score_structure') }} @break
                                            @case('alignment') {{ __('letter.score_alignment') }} @break
                                            @default {{ $label }}
                                        @endswitch
                                    </span>
                                    <span class="text-base font-bold text-indigo-700 dark:text-indigo-300">{{ $value }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if (! empty($jobSkills))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('letter.job_skills') }} ({{ count($jobSkills) }})</h2>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($jobSkills as $skill)
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs dark:bg-[#3E3E3A]">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('letter.checks') }}</h2>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($result['checks'] as $key => $passed)
                                <div class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm {{ $passed ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200' }}">
                                    <span>{{ $passed ? '✓' : '✗' }}</span>
                                    <span>
                                        @switch($key)
                                            @case('has_greeting') {{ __('letter.check_greeting') }} @break
                                            @case('has_closing') {{ __('letter.check_closing') }} @break
                                            @case('has_length') {{ __('letter.check_length') }} @break
                                            @case('has_offer_keywords') {{ __('letter.check_keywords') }} @break
                                            @default {{ $key }}
                                        @endswitch
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if (! empty($result['matched_skills']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('letter.matched_skills') }} ({{ count($result['matched_skills']) }})</h2>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($result['matched_skills'] as $skill)
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-900 dark:bg-blue-950 dark:text-blue-100">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($result['missing_skills']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('letter.missing_skills') }} ({{ count($result['missing_skills']) }})</h2>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($result['missing_skills'] as $skill)
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-800 dark:bg-red-950 dark:text-red-200">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($result['suggestions']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('letter.suggestions') }}</h2>
                            <ul class="list-disc space-y-1 pl-5 text-sm">
                                @foreach ($result['suggestions'] as $suggestion)
                                    <li>{{ $suggestion }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            @endisset
        </main>

        <script>
            document.getElementById('print-letter')?.addEventListener('click', () => window.print());
            document.querySelector('form[action="{{ route('letter.store') }}"]')?.addEventListener('submit', () => {
                const plain = document.getElementById('job_offer')?.value || '';
                const hidden = document.getElementById('job_offer_html');
                if (hidden && !hidden.value) hidden.value = plain;
            });
        </script>
        <style>
            @media print {
                header, nav, form, .print\:hidden { display: none !important; }
                body { background: white !important; color: black !important; }
            }
        </style>
    </body>
</html>
