<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('history.title') }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-4xl flex-col gap-6 p-6 lg:p-10">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ __('history.title') }}</h1>
                    <p class="mt-1 text-sm opacity-70">{{ __('history.subtitle') }}</p>
                </div>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('scan.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.scan') }}</a>
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

            @if (session('status'))
                <div class="rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($scans->isEmpty())
                <div class="rounded-lg border border-[#e3e3e0] bg-white p-8 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <p class="text-sm opacity-70">{{ __('history.empty') }}</p>
                    <a href="{{ route('scan.index') }}" class="mt-4 inline-block rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black">
                        {{ __('history.start') }}
                    </a>
                </div>
            @else
                <div class="overflow-hidden rounded-lg border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#e3e3e0] bg-gray-50 text-xs uppercase tracking-wide opacity-70 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                            <tr>
                                <th class="px-4 py-3">{{ __('history.date') }}</th>
                                <th class="px-4 py-3">{{ __('history.file') }}</th>
                                <th class="px-4 py-3">{{ __('history.score') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($scans as $scan)
                                <tr class="border-b border-[#e3e3e0] last:border-0 dark:border-[#3E3E3A]">
                                    <td class="px-4 py-3 opacity-70">{{ $scan->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $scan->filename }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $scan->score >= 70 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : ($scan->score >= 40 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200') }}">
                                            {{ $scan->score }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('history.show', $scan) }}" class="text-indigo-600 underline underline-offset-2 dark:text-indigo-400">{{ __('history.view') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $scans->links() }}
            @endif
        </main>
    </body>
</html>
