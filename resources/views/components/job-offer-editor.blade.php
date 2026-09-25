@props([
    'templates' => [],
    'templateContents' => [],
    'jobOffer' => null,
    'jobOfferHtml' => null,
    'oldJobOffer' => '',
    'oldJobOfferHtml' => '',
])

@php
    $initial = app(App\Services\JobOfferEditor::class)->editorInitial(
        (string) ($jobOffer ?? ''),
        $jobOfferHtml,
        (string) $oldJobOfferHtml,
        (string) $oldJobOffer,
    );
@endphp

<div>
    <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm font-medium">{{ __('editor.label') }}</span>
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs opacity-60">{{ __('editor.templates') }}</span>
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
                {{ __('editor.clear') }}
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A]">
        <div class="flex flex-wrap items-center gap-0.5 border-b border-[#e3e3e0] bg-gray-50 px-2 py-1 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]">
            <button type="button" data-cmd="bold" title="{{ __('editor.toolbar_bold') }}" class="rounded px-2 py-1 text-sm font-bold hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">B</button>
            <button type="button" data-cmd="italic" title="{{ __('editor.toolbar_italic') }}" class="rounded px-2 py-1 text-sm italic hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">I</button>
            <button type="button" data-cmd="underline" title="{{ __('editor.toolbar_underline') }}" class="rounded px-2 py-1 text-sm underline hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">U</button>
            <span class="mx-1 h-4 w-px bg-[#e3e3e0] dark:bg-[#3E3E3A]"></span>
            <button type="button" data-block="h2" title="{{ __('editor.toolbar_h1') }}" class="rounded px-2 py-1 text-xs font-semibold hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">H1</button>
            <button type="button" data-block="h3" title="{{ __('editor.toolbar_h2') }}" class="rounded px-2 py-1 text-xs font-semibold hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">H2</button>
            <button type="button" data-block="p" title="{{ __('editor.toolbar_p') }}" class="rounded px-2 py-1 text-xs hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">¶</button>
            <span class="mx-1 h-4 w-px bg-[#e3e3e0] dark:bg-[#3E3E3A]"></span>
            <button type="button" data-cmd="insertUnorderedList" title="{{ __('editor.toolbar_bullets') }}" class="rounded px-2 py-1 text-xs hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">• Liste</button>
            <button type="button" data-cmd="insertOrderedList" title="{{ __('editor.toolbar_numbers') }}" class="rounded px-2 py-1 text-xs hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">1. Liste</button>
            <span class="mx-1 h-4 w-px bg-[#e3e3e0] dark:bg-[#3E3E3A]"></span>
            <button type="button" data-block="pre" title="{{ __('editor.toolbar_code') }}" class="rounded px-2 py-1 text-xs font-mono hover:bg-gray-200 dark:hover:bg-[#3E3E3A]">&lt;/&gt;</button>
            <span class="flex-1"></span>
            <span id="char-count" class="text-[0.7rem] text-gray-500">0 / 10000</span>
        </div>

        <div
            id="job-offer-editor"
            class="rich-editor min-h-[18rem] max-h-[32rem] cursor-text overflow-y-auto bg-white p-4 text-sm leading-relaxed outline-none dark:bg-[#161615]"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            aria-label="{{ __('editor.label') }}"
            data-placeholder="{{ __('editor.placeholder') }}"
        >{!! $initial['html'] !!}</div>

        <input type="hidden" name="job_offer" id="job_offer" value="{{ $initial['text'] }}">
        <textarea name="job_offer_html" id="job_offer_html" hidden>{{ $jobOfferHtml ?? $oldJobOfferHtml }}</textarea>
    </div>

    <div class="mt-1 flex justify-between text-xs opacity-60">
        <span>{{ __('editor.hint_paste') }}</span>
        <span>{{ __('editor.hint_plain') }}</span>
    </div>
</div>

<script>
    window.__initJobOfferEditor = function () {
        const templates = @json($templateContents);
        const editor = document.getElementById('job-offer-editor');
        if (!editor || editor.dataset.ready === '1') {
            return;
        }

        editor.dataset.ready = '1';

        const hiddenInput = document.getElementById('job_offer');
        const hiddenHtml = document.getElementById('job_offer_html');
        const charCount = document.getElementById('char-count');
        const form = editor.closest('form');

        function editorText() {
            return (editor.innerText || '').replace(/ /g, ' ');
        }

        function updateCount() {
            if (!charCount) return;
            const length = editorText().trim().length;
            charCount.textContent = length + ' / 10000';
            charCount.classList.toggle('text-red-500', length > 10000);
        }

        function syncHidden() {
            if (hiddenInput) hiddenInput.value = editorText();
            if (hiddenHtml) hiddenHtml.value = editor.innerHTML;
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

        document.getElementById('clear-editor')?.addEventListener('click', () => {
            editor.innerHTML = '';
            updateCount();
            updatePlaceholder();
            syncHidden();
            editor.focus();
        });

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
                if (form) form.requestSubmit();
            }
        });

        form?.addEventListener('formdata', (event) => {
            event.formData.set('job_offer', editorText());
            event.formData.set('job_offer_html', editor.innerHTML);
        });

        updateCount();
        updatePlaceholder();
        syncHidden();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.__initJobOfferEditor);
    } else {
        window.__initJobOfferEditor();
    }
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
