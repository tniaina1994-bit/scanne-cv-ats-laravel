<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Scanner un CV — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <main class="mx-auto flex max-w-4xl flex-col gap-6 p-6 lg:p-10">
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Analyser un CV</h1>
                    <p class="mt-1 text-sm opacity-70">
                        Score ATS · compétences matchées / manquantes · infos personnelles
                    </p>
                </div>
                <nav class="flex gap-4 text-sm">
                    <a href="{{ route('extract.index') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">Extraction</a>
                    <a href="{{ route('home') }}" class="underline underline-offset-4 opacity-70 hover:opacity-100">Accueil</a>
                </nav>
            </header>

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
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">Formulaire de scan</h2>

                <form method="POST" action="{{ route('scan.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="file" class="mb-1 block text-sm font-medium">CV (PDF ou DOCX)</label>
                        <input
                            id="file"
                            type="file"
                            name="file"
                            accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            required
                            class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#1b1b18] file:px-4 file:py-2 file:text-white hover:file:opacity-90 dark:file:bg-[#EDEDEC] dark:file:text-black"
                        >
                        <p class="mt-1 text-xs opacity-60">Formats : .pdf, .docx — max 10 Mo</p>
                    </div>

                    <div>
                        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-medium">Offre d'emploi</span>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-xs opacity-60">Modèles :</span>
                                @foreach ($templates as $key => $label)
                                    <button
                                        type="button"
                                        data-template="{{ $key }}"
                                        class="rounded-full border border-[#e3e3e0] px-2.5 py-0.5 text-xs hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]"
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                                <button
                                    type="button"
                                    id="clear-editor"
                                    class="rounded-full border border-[#e3e3e0] px-2.5 py-0.5 text-xs hover:bg-gray-100 dark:border-[#3E3E3A] dark:hover:bg-[#1D0002]"
                                >
                                    Effacer
                                </button>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex flex-wrap items-center gap-0.5 border-b border-[#e3e3e0] bg-gray-50 px-2 py-1 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
                                <button type="button" data-cmd="bold" title="Gras (Ctrl+B)" class="rounded px-2 py-1 text-sm font-bold hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">B</button>
                                <button type="button" data-cmd="italic" title="Italique (Ctrl+I)" class="rounded px-2 py-1 text-sm italic hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">I</button>
                                <button type="button" data-cmd="underline" title="Souligné (Ctrl+U)" class="rounded px-2 py-1 text-sm underline hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">U</button>
                                <span class="mx-1 h-4 w-px bg-[#e3e3e0] dark:bg-[#3E3E3A]"></span>
                                <button type="button" data-block="h2" title="Titre 1" class="rounded px-2 py-1 text-xs font-semibold hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">H1</button>
                                <button type="button" data-block="h3" title="Titre 2" class="rounded px-2 py-1 text-xs font-semibold hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">H2</button>
                                <button type="button" data-block="p" title="Paragraphe" class="rounded px-2 py-1 text-xs hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">¶</button>
                                <span class="mx-1 h-4 w-px bg-[#e3e3e0] dark:bg-[#3E3E3A]"></span>
                                <button type="button" data-cmd="insertUnorderedList" title="Liste à puces" class="rounded px-2 py-1 text-xs hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">• Liste</button>
                                <button type="button" data-cmd="insertOrderedList" title="Liste numérotée" class="rounded px-2 py-1 text-xs hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">1. Liste</button>
                                <span class="mx-1 h-4 w-px bg-[#e3e3e0] dark:bg-[#3E3E3A]"></span>
                                <button type="button" data-block="pre" title="Code" class="rounded px-2 py-1 text-xs font-mono hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">&lt;/&gt;</button>
                                <span class="flex-1"></span>
                                <span id="char-count" class="text-[0.7rem] text-gray-500">0 / 10000</span>
                            </div>

                            @php
                                $editorHtml = $jobOfferHtml ?? old('job_offer_html');
                                if (! is_string($editorHtml) || trim($editorHtml) === '') {
                                    $editorHtml = e(old('job_offer', $jobOffer ?? ''));
                                } elseif (! preg_match('/<[a-z][\s\S]*>/i', $editorHtml)) {
                                    $editorHtml = e($editorHtml);
                                }
                            @endphp
                            <div
                                id="job-offer-editor"
                                class="rich-editor min-h-[18rem] max-h-[32rem] cursor-text overflow-y-auto bg-white p-4 text-sm leading-relaxed outline-none dark:bg-[#161615]"
                                contenteditable="true"
                                role="textbox"
                                aria-multiline="true"
                                aria-label="Offre d'emploi"
                                data-placeholder="Collez ou écrivez la description du poste ici... (compétences, exigences, missions)"
                            >{!! $editorHtml !!}</div>

                            <input type="hidden" name="job_offer" id="job_offer" value="{{ old('job_offer', $jobOffer ?? '') }}">
                            <textarea name="job_offer_html" id="job_offer_html" hidden>{{ old('job_offer_html', $jobOfferHtml ?? '') }}</textarea>
                        </div>

                        <div class="mt-1 flex justify-between text-xs opacity-60">
                            <span>Le collage retire les mises en formes du presse-papiers · Ctrl+Entrée pour scanner</span>
                            <span>Analyse en texte brut · mise en forme conservée après scan</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <button
                            type="button"
                            id="clear-all"
                            class="rounded-md border border-[#e3e3e0] px-5 py-2 text-sm font-medium opacity-80 hover:opacity-100 dark:border-[#3E3E3A]"
                        >
                            Rejeter / Effacer tout
                        </button>
                        <button
                            type="submit"
                            class="rounded-md bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-[#EDEDEC] dark:text-black"
                        >
                            Scanner
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
                        'exact' => ['Exact', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100'],
                        'synonym' => ['Synonyme', 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100'],
                        'related' => ['Lié', 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100'],
                        'partial' => ['Partiel', 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100'],
                        'semantic' => ['Sémantique', 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-100'],
                    ];
                @endphp

                <section id="scan-results" class="flex flex-col gap-4">
                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 text-center dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">Score de compatibilité</h2>
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full text-2xl font-bold {{ $scoreClass }}">
                            {{ $score }}%
                        </div>
                        <div class="mt-4 flex flex-wrap justify-center gap-2 text-xs">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ $result['filename'] }}</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-[#3E3E3A]">{{ $result['processing_time_ms'] }} ms</span>
                            @if ($result['ocr_used'])
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-900 dark:bg-amber-900 dark:text-amber-100">OCR utilisé</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-green-900 dark:bg-green-900 dark:text-green-100">texte natif</span>
                            @endif
                        </div>
                    </div>

                    @if (! empty($result['job_skills_found']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">Compétences extraites de l'offre ({{ count($result['job_skills_found']) }})</h2>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($result['job_skills_found'] as $skill)
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs dark:bg-[#3E3E3A]">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">Vérifications ATS</h2>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($result['analysis']['ats_checks'] as $checkKey => $checkPassed)
                                <div class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm {{ $checkPassed ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200' }}">
                                    <span>{{ $checkPassed ? '✓' : '✗' }}</span>
                                    <span>
                                        @switch($checkKey)
                                            @case('text_extractable') Texte extractible @break
                                            @case('has_email') Email détecté @break
                                            @case('has_phone') Téléphone détecté @break
                                            @case('has_sections') Sections structurées @break
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
                            'name' => ['Nom', $info['name']],
                            'email' => ['Email', $info['email']],
                            'phone' => ['Téléphone', $info['phone']],
                            'location' => ['Localisation', $info['location']],
                            'nationality' => ['Nationalité', $info['nationality']],
                            'linkedin' => ['LinkedIn', $info['linkedin']],
                            'github' => ['GitHub', $info['github']],
                            'website' => ['Site web', $info['website']],
                            'languages' => ['Langues', ! empty($info['languages']) ? implode(', ', $info['languages']) : ''],
                        ];
                    @endphp

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">Informations personnelles</h2>
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
                                    <span class="block text-[0.7rem] uppercase tracking-wide opacity-50">Permis de conduire</span>
                                    <span class="text-sm font-medium">Oui</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">Scores détaillés</h2>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($result['analysis']['scores'] as $label => $value)
                                <div class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 dark:bg-[#0a0a0a]">
                                    <span class="text-sm opacity-70">
                                        @switch($label)
                                            @case('skills') Compétences @break
                                            @case('experience') Expérience @break
                                            @case('education') Formation @break
                                            @case('ats_quality') Qualité ATS @break
                                            @default {{ $label }}
                                        @endswitch
                                    </span>
                                    <span class="text-base font-bold text-indigo-700 dark:text-indigo-300">{{ $value }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">CV — Analyse</h2>

                        <div class="mb-4">
                            <h3 class="mb-2 text-sm font-semibold">Compétences détectées ({{ count($result['analysis']['cv_skills_detected']) }})</h3>
                            @if (! empty($result['analysis']['cv_skills_detected']))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($result['analysis']['cv_skills_detected'] as $skill)
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs dark:bg-[#3E3E3A]">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm italic opacity-50">Aucune compétence technique détectée</p>
                            @endif
                        </div>

                        <div class="mb-4">
                            <h3 class="mb-2 text-sm font-semibold">Expérience</h3>
                            @if ($result['analysis']['experience_years'] > 0)
                                <p class="text-sm"><strong>{{ $result['analysis']['experience_years'] }} ans</strong> d'expérience détectés</p>
                            @else
                                <p class="text-sm italic opacity-50">Aucune durée d'expérience détectée</p>
                            @endif
                        </div>

                        <div>
                            <h3 class="mb-2 text-sm font-semibold">Formation</h3>
                            @if (! empty($result['analysis']['education_found']))
                                <p class="text-sm">Mots-clés trouvés : <strong>{{ implode(', ', $result['analysis']['education_found']) }}</strong></p>
                            @else
                                <p class="text-sm italic opacity-50">Aucun diplôme détecté</p>
                            @endif
                        </div>
                    </div>

                    @if (! empty($result['analysis']['matched_skills']))
                        <div class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">
                                Compétences correspondantes ({{ count($result['analysis']['matched_skills']) }})
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
                                Compétences manquantes ({{ count($result['analysis']['missing_skills']) }})
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
                            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide opacity-70">Recommandations</h2>
                            <ul class="list-disc space-y-1 pl-5 text-sm">
                                @foreach ($result['analysis']['recommendations'] as $recommendation)
                                    <li>{{ $recommendation }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <details class="rounded-lg border border-[#e3e3e0] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <summary class="cursor-pointer text-sm font-semibold uppercase tracking-wide opacity-70">Aperçu du texte extrait</summary>
                        <pre class="mt-3 max-h-64 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 font-mono text-xs leading-relaxed dark:bg-[#0a0a0a]">{{ $result['cv_text_preview'] }}</pre>
                    </details>
                </section>
            @endisset
        </main>

        <script>
            const templates = @json($templateContents);

const editor = document.getElementById('job-offer-editor');
            const hiddenInput = document.getElementById('job_offer');
            const hiddenHtml = document.getElementById('job_offer_html');
            const charCount = document.getElementById('char-count');
            const form = editor.closest('form');

            function editorText() {
                return (editor.innerText || '').replace(/ /g, ' ');
            }

            function updateCount() {
                const length = editorText().trim().length;
                charCount.textContent = length + ' / 10000';
                charCount.classList.toggle('text-red-500', length > 10000);
            }

function syncHidden() {
                const html = editor.innerHTML;
                hiddenInput.value = editorText();
                if (hiddenHtml) {
                    hiddenHtml.value = html;
                }
            }

            function updatePlaceholder() {
                editor.classList.toggle('is-empty', editorText().trim() === '');
            }

            document.querySelectorAll('[data-cmd]').forEach((button) => {
                button.addEventListener('mousedown', (event) => event.preventDefault());
                button.addEventListener('click', () => {
                    editor.focus();
                    document.execCommand(button.dataset.cmd, false);
                    updateCount();
                    syncHidden();
                });
            });

            document.querySelectorAll('[data-block]').forEach((button) => {
                button.addEventListener('mousedown', (event) => event.preventDefault());
                button.addEventListener('click', () => {
                    editor.focus();
                    document.execCommand('formatBlock', false, button.dataset.block);
                    updateCount();
                    syncHidden();
                });
            });

            document.querySelectorAll('[data-template]').forEach((button) => {
                button.addEventListener('click', () => {
                    editor.innerHTML = templates[button.dataset.template] || '';
                    updateCount();
                    updatePlaceholder();
                    syncHidden();
                    editor.focus();
                });
            });

            document.getElementById('clear-editor').addEventListener('click', () => {
                editor.innerHTML = '';
                updateCount();
                updatePlaceholder();
                syncHidden();
                editor.focus();
            });

            document.getElementById('clear-all').addEventListener('click', () => {
                form.reset();
                editor.innerHTML = '';
                hiddenInput.value = '';
                if (hiddenHtml) {
                    hiddenHtml.value = '';
                }
                updateCount();
                updatePlaceholder();

                document.getElementById('scan-results')?.setAttribute('hidden', 'hidden');
                document.querySelectorAll('#scan-errors').forEach((node) => node.setAttribute('hidden', 'hidden'));
                editor.focus();
            });

            // Collage : n'insère que du texte brut (retire les mises en formes du presse-papiers).
            editor.addEventListener('paste', (event) => {
                event.preventDefault();
                const clipboard = event.clipboardData || window.clipboardData;
                const text = clipboard.getData('text/plain');
                document.execCommand('insertText', false, text);
                updateCount();
                updatePlaceholder();
                syncHidden();
            });

            editor.addEventListener('input', () => {
                updateCount();
                updatePlaceholder();
                syncHidden();
            });

            editor.addEventListener('keydown', (event) => {
                if (event.ctrlKey && event.key === 'Enter') {
                    event.preventDefault();
                    syncHidden();
                    if (form) {
                        form.requestSubmit();
                    }
                }
            });

            form.addEventListener('submit', () => {
                syncHidden();
            });

            // Garantit l'HTML même si un autre handler a stoppÉ la propagation.
            form.addEventListener('formdata', (event) => {
                event.formData.set('job_offer', editorText());
                event.formData.set('job_offer_html', editor.innerHTML);
            });

            updateCount();
            updatePlaceholder();
            syncHidden();
        </script>
        <style>
            .rich-editor:empty::before,
            .rich-editor.is-empty::before {
                content: attr(data-placeholder);
                color: #9ca3af;
                pointer-events: none;
            }
            .rich-editor h2 { font-size: 1.25rem; font-weight: 600; margin: 0.5rem 0; }
            .rich-editor h3 { font-size: 1.1rem; font-weight: 600; margin: 0.5rem 0; }
            .rich-editor p { margin: 0.3rem 0; }
            .rich-editor ul, .rich-editor ol { padding-left: 1.5rem; margin: 0.3rem 0; }
            .rich-editor li { margin-bottom: 0.15rem; }
            .rich-editor pre {
                background: #0f172a;
                color: #e2e8f0;
                padding: 0.75rem;
                border-radius: 0.375rem;
                overflow-x: auto;
                margin: 0.5rem 0;
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 0.85em;
            }
        </style>
    </body>
</html>
