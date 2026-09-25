<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('scan.title') }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-4xl flex-col gap-6 p-6 lg:p-10">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ __('scan.title') }}</h1>
                    <p class="mt-1 text-sm opacity-70">
                        {{ __('scan.subtitle') }}
                    </p>
                </div>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('extract.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.extract') }}</a>
                    <a href="{{ route('letter.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.letter') }}</a>
                    <a href="{{ route('history.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.history') }}</a>
                    <a href="{{ route('compare.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ __('nav.compare') }}</a>
                    <a href="?lang={{ app()->getLocale() === 'fr' ? 'en' : 'fr' }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">{{ app()->getLocale() === 'fr' ? 'EN' : 'FR' }}</a>
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

            @if (session('scan.pending'))
                <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    <p>{{ session('scan.pending') }}</p>
                    <p class="mt-1 text-xs opacity-80">
                        {{ __('scan.ocr_async_hint') }}
                        <a href="{{ route('history.index') }}" class="ml-1 underline underline-offset-4">{{ __('scan.view_history') }}</a>
                    </p>
                </div>
            @endif

            @if (isset($error))
                <div id="scan-errors" class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    {{ $error }}
                </div>
            @endif

            @if ($errors->any())
                <div id="scan-errors" class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.form_title') }}</h2>

                <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="file" class="mb-1 block text-sm font-medium">{{ __('scan.file_label') }}</label>
                        <div
                            id="drop-zone"
                            class="rounded-md border-2 border-dashed border-[#e3e3e0] p-4 text-center transition dark:border-[#3E3E3A]"
                        >
                            <input
                                id="file"
                                type="file"
                                name="file"
                                accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                required
                                class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#1b1b18] file:px-4 file:py-2 file:text-white hover:file:opacity-90 dark:file:bg-[#EDEDEC] dark:file:text-black"
                            >
                            <p class="mt-2 text-xs opacity-60">{{ __('scan.drop_hint') }}</p>
                        </div>
                        <div id="scan-progress" class="mt-3 hidden">
                            <div class="mb-1 flex justify-between text-xs opacity-70">
                                <span id="scan-progress-label">{{ __('scan.progress') }}</span>
                                <span id="scan-progress-pct">0%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-[#3E3E3A]">
                                <div id="scan-progress-bar" class="h-full w-0 rounded-full bg-indigo-600 transition-all duration-500"></div>
                            </div>
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

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <button
                            type="button"
                            id="clear-all"
                            class="rounded-md border border-[#e3e3e0] px-5 py-2 text-sm font-medium opacity-80 hover:opacity-100 dark:border-[#3E3E3A]"
                        >
                            {{ __('scan.clear') }}
                        </button>
                        <button
                            type="submit"
                            class="rounded-md bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black"
                        >
                            {{ __('scan.submit') }}
                        </button>
                    </div>
                </form>
            </section>

            @isset($result)
                @php
                    $score = $result['analysis']['global_score'];
                    $scoreClass = $score >= 70
                        ? 'bg-green-600 text-white'
                        : ($score >= 40 ? 'bg-amber-500 text-white' : 'bg-red-500 text-white');
                    $matchLabels = [
                        'exact' => [__('scan.match_exact'), 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100'],
                        'synonym' => [__('scan.match_synonym'), 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100'],
                        'related' => [__('scan.match_related'), 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100'],
                        'partial' => [__('scan.match_partial'), 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100'],
                        'semantic' => [__('scan.match_semantic'), 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-100'],
                    ];
                @endphp

                <section id="scan-results" class="flex flex-col gap-4">
                    <div class="print:hidden">
                        <button type="button" id="print-scan-result" class="rounded-md border border-[#e3e3e0] px-4 py-2 text-sm font-medium hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]">
                            {{ __('scan.print') }}
                        </button>
                    </div>
                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 text-center dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0 print:bg-white">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.score') }}</h2>
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full text-2xl font-bold {{ $scoreClass }}">
                            {{ $score }}%
                        </div>
                        <div class="mt-4 flex flex-wrap justify-center gap-2 text-xs">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ $result['filename'] }}</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ $result['processing_time_ms'] }} ms</span>
                            @if ($result['ocr_used'])
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-900 dark:bg-amber-900 dark:text-amber-100">{{ __('scan.ocr_used') }}</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-green-900 dark:bg-green-900 dark:text-green-100">{{ __('scan.native_text') }}</span>
                            @endif
                        </div>
                    </div>

                    @if (! empty($result['job_skills_found']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.job_skills') }} ({{ count($result['job_skills_found']) }})</h2>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($result['job_skills_found'] as $skill)
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs dark:bg-[#3E3E3A]">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.ats_checks') }}</h2>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($result['analysis']['ats_checks'] as $checkKey => $checkPassed)
                                <div class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm {{ $checkPassed ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200' }}">
                                    <span>{{ $checkPassed ? '✓' : '✗' }}</span>
                                    <span>
                                        @switch($checkKey)
                                            @case('text_extractable') {{ __('scan.check_text') }} @break
                                            @case('has_email') {{ __('scan.check_email') }} @break
                                            @case('has_phone') {{ __('scan.check_phone') }} @break
                                            @case('has_sections') {{ __('scan.check_sections') }} @break
                                            @default {{ $checkKey }}
                                        @endswitch
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @php
                        $info = $result['analysis']['personal_info'];
                        $infoItems = [
                            'name' => [__('scan.info_name'), $info['name']],
                            'email' => [__('scan.info_email'), $info['email']],
                            'phone' => [__('scan.info_phone'), $info['phone']],
                            'location' => [__('scan.info_location'), $info['location']],
                            'nationality' => [__('scan.info_nationality'), $info['nationality']],
                            'linkedin' => [__('scan.info_linkedin'), $info['linkedin']],
                            'github' => [__('scan.info_github'), $info['github']],
                            'website' => [__('scan.info_website'), $info['website']],
                            'languages' => [__('scan.info_languages'), ! empty($info['languages']) ? implode(', ', $info['languages']) : ''],
                        ];
                    @endphp

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.personal_info') }}</h2>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($infoItems as $item)
                                @if ($item[1] !== '' && $item[1] !== null)
                                    <div class="rounded-md border-l-2 border-blue-600 bg-gray-50 px-3 py-2 dark:bg-[#0a0a0a]">
                                        <span class="block text-[0.7rem] uppercase tracking-wide opacity-50">{{ $item[0] }}</span>
                                        <span class="text-sm font-medium">{{ $item[1] }}</span>
                                    </div>
                                @endif
                            @endforeach
                            @if ($info['has_driving_license'])
                                <div class="rounded-md border-l-2 border-blue-600 bg-gray-50 px-3 py-2 dark:bg-[#0a0a0a]">
                                    <span class="block text-[0.7rem] uppercase tracking-wide opacity-50">{{ __('scan.info_driving') }}</span>
                                    <span class="text-sm font-medium">{{ __('scan.info_yes') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.detailed_scores') }}</h2>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($result['analysis']['scores'] as $label => $value)
                                <div class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 dark:bg-[#0a0a0a]">
                                    <span class="text-sm opacity-70">
                                        @switch($label)
                                            @case('skills') {{ __('scan.score_skills') }} @break
                                            @case('experience') {{ __('scan.score_experience') }} @break
                                            @case('education') {{ __('scan.score_education') }} @break
                                            @case('ats_quality') {{ __('scan.score_ats') }} @break
                                            @case('semantic') {{ __('scan.score_semantic') }} @break
                                            @default {{ $label }}
                                        @endswitch
                                    </span>
                                    <span class="text-base font-bold text-indigo-700 dark:text-indigo-300">{{ $value }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.sections_found') }}</h2>
                        @if (! empty($result['analysis']['section_keys']))
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($result['analysis']['section_keys'] as $sectionKey)
                                    <span class="rounded-full bg-teal-100 px-2.5 py-1 text-xs text-teal-900 dark:bg-teal-950 dark:text-teal-100">{{ $sectionKey }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm italic opacity-50">{{ __('scan.no_sections') }}</p>
                        @endif
                        @if (! empty($result['analysis']['experience_required']))
                            <p class="mt-3 text-sm">
                                {{ __('scan.experience_required') }} :
                                <strong>{{ $result['analysis']['experience_required'] }} {{ __('scan.years') }}</strong>
                                @if (! empty($result['analysis']['experience_gap']))
                                    · {{ __('scan.experience_gap') }} :
                                    <strong>{{ $result['analysis']['experience_gap'] }} {{ __('scan.years') }}</strong>
                                @endif
                            </p>
                        @endif
                    </div>

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.cv_analysis') }}</h2>

                        <div class="mb-4">
                            <h3 class="mb-2 text-sm font-semibold">{{ __('scan.detected_skills') }} ({{ count($result['analysis']['cv_skills_detected']) }})</h3>
                            @if (! empty($result['analysis']['cv_skills_detected']))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($result['analysis']['cv_skills_detected'] as $skill)
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs dark:bg-[#3E3E3A]">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm italic opacity-50">{{ __('scan.no_skills') }}</p>
                            @endif
                        </div>

                        <div class="mb-4">
                            <h3 class="mb-2 text-sm font-semibold">{{ __('scan.experience') }}</h3>
                            @if ($result['analysis']['experience_years'] > 0)
                                <p class="text-sm"><strong>{{ $result['analysis']['experience_years'] }}</strong> {{ __('scan.experience_years') }}</p>
                            @else
                                <p class="text-sm italic opacity-50">{{ __('scan.no_experience') }}</p>
                            @endif
                        </div>

                        <div>
                            <h3 class="mb-2 text-sm font-semibold">{{ __('scan.education') }}</h3>
                            @if (! empty($result['analysis']['education_found']))
                                <p class="text-sm">{{ __('scan.education_found') }} : <strong>{{ implode(', ', $result['analysis']['education_found']) }}</strong></p>
                            @else
                                <p class="text-sm italic opacity-50">{{ __('scan.no_education') }}</p>
                            @endif
                        </div>
                    </div>

                    @if (! empty($result['analysis']['matched_skills']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">
                                {{ __('scan.matched_skills') }} ({{ count($result['analysis']['matched_skills']) }})
                            </h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($result['analysis']['matched_skills'] as $skill)
                                    @php
                                        $type = $result['analysis']['matched_types'][$skill] ?? 'exact';
                                        $typeKey = 'exact';
                                        foreach (['synonym', 'related', 'partial', 'semantic'] as $candidate) {
                                            if (str_starts_with($type, $candidate)) {
                                                $typeKey = $candidate;
                                                break;
                                            }
                                        }
                                        [$badgeLabel, $badgeClass] = $matchLabels[$typeKey];
                                    @endphp
                                    <span class="flex items-center gap-1.5">
                                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-900 dark:bg-blue-950 dark:text-blue-100">{{ $skill }}</span>
                                        <span class="rounded-full px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($result['analysis']['missing_skills']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">
                                {{ __('scan.missing_skills') }} ({{ count($result['analysis']['missing_skills']) }})
                            </h2>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($result['analysis']['missing_skills'] as $skill)
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-800 dark:bg-red-950 dark:text-red-200">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($result['analysis']['recommendations']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.recommendations') }}</h2>
                            <ul class="list-disc space-y-1 pl-5 text-sm">
                                @foreach ($result['analysis']['recommendations'] as $recommendation)
                                    <li>{{ $recommendation }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($result['analysis']['reformulation_suggestions']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615] print:border-0">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.suggestions') }}</h2>
                            <ul class="list-disc space-y-1 pl-5 text-sm">
                                @foreach ($result['analysis']['reformulation_suggestions'] as $suggestion)
                                    <li>{{ $suggestion }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <details class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <summary class="cursor-pointer text-sm font-semibold uppercase tracking-wide opacity-70">{{ __('scan.text_preview') }}</summary>
                        <pre class="mt-3 max-h-64 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 font-mono text-xs leading-relaxed dark:bg-[#0a0a0a]">{{ $result['cv_text_preview'] }}</pre>
                    </details>
                </section>
            @endisset
        </main>

        <script>
            document.getElementById('print-report')?.addEventListener('click', () => window.print());

            const form = document.querySelector('form[action="{{ route('scan.store') }}"]');
            if (form) {
                form.addEventListener('formdata', (event) => {
                    const editor = document.getElementById('job-offer-editor');
                    if (editor) {
                        event.formData.set('job_offer', (editor.innerText || '').replace(/ /g, ' '));
                        event.formData.set('job_offer_html', editor.innerHTML);
                    }
                });

                form.addEventListener('submit', () => {
                    const progress = document.getElementById('scan-progress');
                    const bar = document.getElementById('scan-progress-bar');
                    const label = document.getElementById('scan-progress-label');
                    const pct = document.getElementById('scan-progress-pct');
                    if (progress && bar) {
                        progress.classList.remove('hidden');
                        const steps = [
                            [35, @json(__('scan.progress_extract'))],
                            [70, @json(__('scan.progress_matching'))],
                            [90, @json(__('scan.progress_score'))],
                            [100, @json(__('scan.progress_send'))],
                        ];
                        let i = 0;
                        const tick = () => {
                            if (i >= steps.length) return;
                            const [value, text] = steps[i++];
                            bar.style.width = value + '%';
                            if (pct) pct.textContent = value + '%';
                            if (label) label.textContent = text;
                            if (i < steps.length) setTimeout(tick, 350);
                        };
                        tick();
                    }
                });
            }

            document.getElementById('clear-all')?.addEventListener('click', () => {
                form?.reset();
                const editor = document.getElementById('job-offer-editor');
                const hiddenInput = document.getElementById('job_offer');
                const hiddenHtml = document.getElementById('job_offer_html');
                if (editor) {
                    editor.innerHTML = '';
                    editor.classList.add('is-empty');
                }
                if (hiddenInput) hiddenInput.value = '';
                if (hiddenHtml) hiddenHtml.value = '';
                document.getElementById('scan-results')?.setAttribute('hidden', 'hidden');
                document.querySelectorAll('#scan-errors').forEach((node) => node.setAttribute('hidden', 'hidden'));
                editor?.focus();
            });

            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file');
            if (dropZone && fileInput) {
                const setActive = (active) => {
                    dropZone.classList.toggle('border-indigo-500', active);
                    dropZone.classList.toggle('bg-indigo-50', active);
                    dropZone.classList.toggle('dark:bg-indigo-950', active);
                };
                ['dragenter', 'dragover'].forEach((name) => {
                    dropZone.addEventListener(name, (event) => {
                        event.preventDefault();
                        setActive(true);
                    });
                });
                ['dragleave', 'drop'].forEach((name) => {
                    dropZone.addEventListener(name, (event) => {
                        event.preventDefault();
                        setActive(false);
                    });
                });
                dropZone.addEventListener('drop', (event) => {
                    const files = event.dataTransfer?.files;
                    if (files && files.length > 0) {
                        fileInput.files = files;
                    }
                });
            }

            document.getElementById('print-scan-result')?.addEventListener('click', () => window.print());
        </script>
        <style>
            @media print {
                header, nav, #scan-progress, #drop-zone, form, .print\:hidden { display: none !important; }
                body { background: white !important; color: black !important; }
                #scan-results section, #scan-results div { border-color: #e5e7eb !important; background: white !important; }
            }
        </style>
    </body>
</html>
