<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('skills.title') }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-4xl flex-col gap-6 p-6 lg:p-10">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ __('skills.title') }}</h1>
                    <p class="mt-1 text-sm opacity-70">{{ __('skills.subtitle') }}</p>
                </div>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('scan.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.scan') }}</a>
                    <a href="{{ route('history.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.history') }}</a>
                    <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.logout') }}</button>
                    </form>
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

            @if ($errors->any())
                <div class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-wrap gap-3 text-sm">
                <span class="rounded-full bg-gray-100 px-3 py-1 dark:bg-[#3E3E3A]">{{ __('skills.builtin_count') }} : {{ $builtinCount }}</span>
                <span class="rounded-full bg-indigo-100 px-3 py-1 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200">{{ __('skills.custom_count') }} : {{ $customSkills->count() }}</span>
            </div>

            <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('skills.add_title') }}</h2>
                <form method="POST" action="{{ route('admin.skills.store') }}" class="grid gap-3 sm:grid-cols-3">
                    @csrf
                    <div class="sm:col-span-1">
                        <label for="name" class="mb-1 block text-sm font-medium">{{ __('skills.name') }}</label>
                        <input id="name" name="name" type="text" required maxlength="80" value="{{ old('name') }}" class="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="category" class="mb-1 block text-sm font-medium">{{ __('skills.category') }}</label>
                        <input id="category" name="category" type="text" maxlength="60" value="{{ old('category') }}" class="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                    </div>
                    <div class="sm:col-span-1">
                        <label for="synonyms" class="mb-1 block text-sm font-medium">{{ __('skills.synonyms') }}</label>
                        <input id="synonyms" name="synonyms" type="text" maxlength="500" value="{{ old('synonyms') }}" class="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                    </div>
                    <div class="sm:col-span-3">
                        <button type="submit" class="rounded-md bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black">
                            {{ __('skills.create') }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('skills.custom_count') }}</h2>

                @if ($customSkills->isEmpty())
                    <p class="text-sm italic opacity-50">{{ __('skills.empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-[#e3e3e0] text-xs uppercase tracking-wide opacity-60 dark:border-[#3E3E3A]">
                                    <th class="py-2 pr-3">{{ __('skills.name') }}</th>
                                    <th class="py-2 pr-3">{{ __('skills.category') }}</th>
                                    <th class="py-2 pr-3">{{ __('skills.synonyms') }}</th>
                                    <th class="py-2 pr-3">{{ __('skills.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customSkills as $skill)
                                    <tr class="border-b border-[#e3e3e0] last:border-0 dark:border-[#3E3E3A]">
                                        <td class="py-2 pr-3 font-medium">{{ $skill->name }}</td>
                                        <td class="py-2 pr-3 opacity-70">{{ $skill->category ?: __('skills.none') }}</td>
                                        <td class="py-2 pr-3 opacity-70">{{ $skill->synonyms ? implode(', ', $skill->synonyms) : __('skills.none') }}</td>
                                        <td class="py-2 pr-3">
                                            <form method="POST" action="{{ route('admin.skills.update', $skill) }}" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="name" value="{{ old('name', $skill->name) }}">
                                                <input type="hidden" name="category" value="{{ old('category', $skill->category) }}">
                                                <input type="hidden" name="synonyms" value="{{ old('synonyms', $skill->synonyms ? implode(', ', $skill->synonyms) : '') }}">
                                                <button type="submit" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('skills.save') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.skills.destroy', $skill) }}" class="ml-3 inline" onsubmit="return confirm('{{ __('skills.confirm_delete') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 underline underline-offset-4 hover:opacity-80 dark:text-red-400">{{ __('skills.delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </main>
    </body>
</html>
