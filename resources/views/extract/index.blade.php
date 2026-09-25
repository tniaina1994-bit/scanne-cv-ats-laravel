<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('extract.title') }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-3xl flex-col gap-6 p-6 lg:p-10">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ __('extract.title') }}</h1>
                    <p class="mt-1 text-sm opacity-70">
                        {{ __('extract.subtitle') }}
                    </p>
                </div>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('scan.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.scan') }}</a>
                    <a href="{{ route('history.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.history') }}</a>
                    @auth
                        <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.logout') }}</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.login') }}</a>
                    @endauth
                    <a href="{{ route('home') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.home') }}</a>
                    <button type="button" data-theme-toggle class="underline underline-offset-4 opacity-70 hover:opacity-100">
                        <span data-theme-label>{{ __('nav.dark') }}</span>
                    </button>
                </nav>
            </header>

            @if (isset($error))
                <div id="extract-errors" class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    {{ $error }}
                </div>
            @endif

            @if ($errors->any())
                <div id="extract-errors" class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('extract.file_section') }}</h2>

                <form method="POST" action="{{ route('extract.store') }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-center" id="extract-form">
                    @csrf
                    <input
                        type="file"
                        name="file"
                        id="file"
                        accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        required
                        class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#1b1b18] file:px-4 file:py-2 file:text-white hover:file:opacity-90 dark:file:bg-[#EDEDEC] dark:file:text-black"
                    >
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="submit"
                            class="shrink-0 rounded-md bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black"
                        >
                            {{ __('extract.submit') }}
                        </button>
                        <button
                            type="button"
                            id="clear-all"
                            class="shrink-0 rounded-md border border-[#e3e3e0] px-5 py-2 text-sm font-medium opacity-80 hover:opacity-100 dark:border-[#3E3E3A]"
                        >
                            {{ __('extract.clear') }}
                        </button>
                    </div>
                </form>

                <p class="mt-2 text-xs opacity-60">{{ __('extract.formats') }}</p>

                @if (! empty($samples))
                    <div class="mt-5 border-t border-[#e3e3e0] pt-4 dark:border-[#3E3E3A]">
                        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('extract.samples') }}</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($samples as $sample)
                                <form method="POST" action="{{ route('extract.sample') }}">
                                    @csrf
                                    <input type="hidden" name="sample" value="{{ $sample['name'] }}">
                                    <button
                                        type="submit"
                                        class="rounded-full border border-[#e3e3e0] px-3 py-1 text-xs hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]"
                                    >
                                        {{ $sample['label'] }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            @isset($result)
                <section id="extract-results" class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('extract.result') }}</h2>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ $result['filename'] }}</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 uppercase dark:bg-[#3E3E3A]">{{ $result['extension'] }}</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ number_format($result['size'] / 1024, 0) }} KB</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ $result['chars'] }} {{ __('extract.chars') }}</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ $result['ms'] }} ms</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ $result['method'] }}</span>
                            @if ($result['ocrUsed'])
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-900 dark:bg-amber-900 dark:text-amber-100">{{ __('extract.ocr_used') }}</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-green-900 dark:bg-green-900 dark:text-green-100">{{ __('extract.native_text') }}</span>
                            @endif
                        </div>
                    </div>

                    <pre class="max-h-[32rem] overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-4 font-mono text-xs leading-relaxed dark:bg-[#0a0a0a]">{{ $result['text'] }}</pre>
                </section>
            @endisset
        </main>

        <script>
            document.getElementById('clear-all')?.addEventListener('click', () => {
                document.getElementById('extract-form')?.reset();
                document.getElementById('extract-results')?.setAttribute('hidden', 'hidden');
                document.querySelectorAll('#extract-errors').forEach((node) => node.setAttribute('hidden', 'hidden'));
            });
        </script>
    </body>
</html>
