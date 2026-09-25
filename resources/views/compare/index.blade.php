<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('compare.title') }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-5xl flex-col gap-6 p-6 lg:p-10">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ __('compare.title') }}</h1>
                    <p class="mt-1 text-sm opacity-70">{{ __('compare.subtitle') }}</p>
                </div>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('scan.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.scan') }}</a>
                    <a href="{{ route('history.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.history') }}</a>
                    <a href="{{ route('home') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.home') }}</a>
                    <button type="button" data-theme-toggle class="underline underline-offset-4 opacity-70 hover:opacity-100">
                        <span data-theme-label>{{ __('nav.dark') }}</span>
                    </button>
                </nav>
            </header>

            @if (! empty($error))
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

            <form method="POST" action="{{ route('compare.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="file_a" class="mb-1 block text-sm font-medium">{{ __('compare.file_a') }}</label>
                        <input id="file_a" name="file_a" type="file" accept=".pdf,.docx" required class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#1b1b18] file:px-4 file:py-2 file:text-white dark:file:bg-[#EDEDEC] dark:file:text-black">
                    </div>
                    <div>
                        <label for="file_b" class="mb-1 block text-sm font-medium">{{ __('compare.file_b') }}</label>
                        <input id="file_b" name="file_b" type="file" accept=".pdf,.docx" required class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#1b1b18] file:px-4 file:py-2 file:text-white dark:file:bg-[#EDEDEC] dark:file:text-black">
                    </div>
                </div>
                <div>
                    <x-job-offer-editor
                        :templates="$templates"
                        :template-contents="$templateContents"
                        :job-offer="$jobOffer"
                        :job-offer-html="$jobOfferHtml"
                        :old-job-offer="old('job_offer')"
                        :old-job-offer-html="old('job_offer_html')"
                    />
                </div>
                <button type="submit" class="justify-self-end rounded-md bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black">
                    {{ __('compare.submit') }}
                </button>
            </form>

            @if (! empty($comparison))
                @php
                    $scoreA = (int) ($comparison['a']['analysis']['global_score'] ?? 0);
                    $scoreB = (int) ($comparison['b']['analysis']['global_score'] ?? 0);
                    $missingA = $comparison['a']['analysis']['missing_skills'] ?? [];
                    $missingB = $comparison['b']['analysis']['missing_skills'] ?? [];
                    $onlyA = array_values(array_diff($missingB, $missingA));
                    $onlyB = array_values(array_diff($missingA, $missingB));
                @endphp

                <section class="grid gap-4 sm:grid-cols-2">
                    @foreach (['a' => __('compare.file_a'), 'b' => __('compare.file_b')] as $key => $label)
                        @php $side = $comparison[$key]; @endphp
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <h2 class="text-sm font-semibold uppercase tracking-wide opacity-70">{{ $label }}</h2>
                                <span class="text-xs opacity-60">{{ $side['filename'] }}</span>
                            </div>
                            @php
                                $score = (int) $side['analysis']['global_score'];
                                $scoreClass = $score >= 70 ? 'bg-green-600' : ($score >= 40 ? 'bg-amber-500' : 'bg-red-500');
                            @endphp
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full text-xl font-bold text-white {{ $scoreClass }}">
                                {{ $score }}%
                            </div>
                            <p class="mt-3 text-center text-xs opacity-70">
                                {{ count($side['analysis']['matched_skills'] ?? []) }} {{ __('compare.matched') }} ·
                                {{ count($side['analysis']['missing_skills'] ?? []) }} {{ __('compare.missing') }}
                                @if ($side['ocr_used'])
                                    · OCR
                                @endif
                            </p>
                            @if (! empty($side['analysis']['missing_skills']))
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach (array_slice($side['analysis']['missing_skills'], 0, 8) as $skill)
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-800 dark:bg-red-950 dark:text-red-200">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </section>

                <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('compare.gap') }}</h2>
                    <p class="text-sm">
                        {{ __('compare.gap_score') }} :
                        <strong>{{ $scoreA >= $scoreB ? __('compare.file_a') : __('compare.file_b') }} +{{ abs($scoreA - $scoreB) }} pt(s)</strong>
                        ({{ $scoreA }}% vs {{ $scoreB }}%)
                    </p>
                    @if ($onlyA !== [] || $onlyB !== [])
                        <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <p class="mb-1 font-medium">{{ __('compare.only_a') }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($onlyA as $skill)
                                        <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs text-orange-900 dark:bg-orange-950 dark:text-orange-100">{{ $skill }}</span>
                                    @empty
                                        <span class="text-xs opacity-60">—</span>
                                    @endforelse
                                </div>
                            </div>
                            <div>
                                <p class="mb-1 font-medium">{{ __('compare.only_b') }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($onlyB as $skill)
                                        <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs text-orange-900 dark:bg-orange-950 dark:text-orange-100">{{ $skill }}</span>
                                    @empty
                                        <span class="text-xs opacity-60">—</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </section>

                <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('compare.suggestions') }}</h2>
                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach (array_merge(
                            array_slice($comparison['a']['analysis']['reformulation_suggestions'] ?? [], 0, 3),
                            array_slice($comparison['b']['analysis']['reformulation_suggestions'] ?? [], 0, 3),
                        ) as $suggestion)
                            <li>{{ $suggestion }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </main>
        <script>
            document.getElementById('print-report')?.addEventListener('click', () => window.print());
        </script>
    </body>
</html>
