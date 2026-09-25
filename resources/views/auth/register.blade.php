<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Inscription — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-md flex-col gap-6 p-6 lg:p-10">
            <header>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">ATS CV Analyzer</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ __('auth.register_title') }}</h1>
                <p class="mt-1 text-sm opacity-70">{{ __('auth.register_subtitle') }}</p>
            </header>

            @if ($errors->any())
                <div class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('auth.register.store') }}" class="flex flex-col gap-4 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                @csrf

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium">{{ __('auth.name') }}</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        autocomplete="name"
                        required
                        value="{{ old('name') }}"
                        class="block w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">{{ __('auth.email') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="{{ old('email') }}"
                        class="block w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">{{ __('auth.password') }} <span class="opacity-60">{{ __('auth.password_min') }}</span></label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="block w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium">{{ __('auth.password_confirm') }}</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="block w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                    >
                </div>

                <button type="submit" class="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black">
                    {{ __('auth.register_submit') }}
                </button>
            </form>

            <p class="text-sm opacity-70">
                {{ __('auth.has_account') }}
                <a href="{{ route('login') }}" class="text-indigo-600 underline underline-offset-4 dark:text-indigo-400">{{ __('auth.login_link') }}</a>
            </p>
            <nav class="flex gap-4 text-sm">
                <a href="{{ route('home') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.home') }}</a>
                <a href="{{ route('scan.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.scan') }}</a>
            </nav>
        </main>
    </body>
</html>
