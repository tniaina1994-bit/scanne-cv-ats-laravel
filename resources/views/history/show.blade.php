<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('history.detail') }} #{{ $scan->id }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-4xl flex-col gap-6 p-6 lg:p-10">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ __('history.detail') }}</h1>
                    <p class="mt-1 text-sm opacity-70">
                        {{ $scan->filename }} · {{ $scan->created_at?->format('d/m/Y H:i') }}
                        @if (! empty($isShared))
                            · {{ __('history.shared') }}
                        @endif
                    </p>
                </div>
                <nav class="flex flex-wrap gap-3 text-sm">
<a href="{{ route('history.export', $scan) }}" class="rounded-md border border-[#e3e3e0] px-3 py-1.5 hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        {{ __('history.export') }}
                    </a>
                    <button type="button" id="print-report" class="rounded-md border border-[#e3e3e0] px-3 py-1.5 hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                        {{ __('history.print') }}
                    </button>
                    @if (empty($isShared))
                        <button type="button" id="copy-share" data-url="{{ $shareUrl }}" data-copied="{{ __('history.copied') }}" class="rounded-md border border-[#e3e3e0] px-3 py-1.5 hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                            {{ __('history.share') }}
                        </button>
                        <form method="POST" action="{{ route('history.destroy', $scan) }}" onsubmit="return confirm('{{ __('history.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950">
                                {{ __('history.delete') }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('history.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.history') }}</a>
                    <a href="{{ route('home') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.home') }}</a>
                    <button type="button" data-theme-toggle class="underline underline-offset-4 opacity-70 hover:opacity-100">
                        <span data-theme-label>{{ __('nav.dark') }}</span>
                    </button>
                </nav>
            </header>

            @php
                $result = $scan->result;
                $analysis = $result['analysis'] ?? [];
                $score = (int) $scan->score;
                $scoreClass = $score >= 70
                    ? 'bg-green-600 text-white'
                    : ($score >= 40 ? 'bg-amber-500 text-white' : 'bg-red-500 text-white');
            @endphp

            <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 text-center dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0 print:bg-white">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.score') }}</h2>
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full text-2xl font-bold {{ $scoreClass }}">
                    {{ $score }}%
                </div>
            </section>

            <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('history.job_offer') }}</h2>
                <div class="prose prose-sm max-w-none dark:prose-invert">{!! $scan->job_offer_html !!}</div>
            </section>

            @if (! empty($analysis['matched_skills']))
                <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.matched_skills') }}</h2>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($analysis['matched_skills'] as $skill)
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-900 dark:bg-blue-950 dark:text-blue-100">{{ $skill }}</span>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! empty($analysis['missing_skills']))
                <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.missing_skills') }}</h2>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($analysis['missing_skills'] as $skill)
                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-800 dark:bg-red-950 dark:text-red-200">{{ $skill }}</span>
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! empty($analysis['recommendations']))
                <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.recommendations') }}</h2>
                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach ($analysis['recommendations'] as $recommendation)
                            <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (! empty($analysis['reformulation_suggestions']))
                <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.suggestions') }}</h2>
                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach ($analysis['reformulation_suggestions'] as $suggestion)
                            <li>{{ $suggestion }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </main>

        <script>
            document.getElementById('print-report')?.addEventListener('click', () => window.print());
            document.getElementById('copy-share')?.addEventListener('click', async (event) => {
                const url = event.currentTarget.dataset.url;
                try {
                    await navigator.clipboard.writeText(url);
                    event.currentTarget.textContent = event.currentTarget.dataset.copied || 'Copied';
                } catch {
                    window.prompt('Copier le lien :', url);
                }
            });
        </script>
        <style>
            @media print {
                nav, #copy-share, form { display: none !important; }
                body { background: white !important; color: black !important; }
            }
        </style>
    </body>
</html>
