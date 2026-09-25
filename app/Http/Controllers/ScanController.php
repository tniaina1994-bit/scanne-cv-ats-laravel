<?php

namespace App\Http\Controllers;

use App\Jobs\OcrExtractJob;
use App\Models\Scan;
use App\Services\CvAnalyzer;
use App\Services\DocumentTextExtractor;
use App\Services\JobOfferEditor;
use App\Services\ReformulationSuggester;
use App\Services\SkillLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ScanController extends Controller
{
    public function __construct(
        private readonly DocumentTextExtractor $extractor,
        private readonly CvAnalyzer $analyzer,
        private readonly SkillLibrary $skillLibrary,
        private readonly ReformulationSuggester $reformulator,
        private readonly JobOfferEditor $jobOfferEditor,
    ) {}

    public function index(): View
    {
        return $this->viewResult(
            error: session('scan.error'),
            jobOffer: session('scan.job_offer'),
            jobOfferHtml: session('scan.job_offer_html'),
            result: session('scan.result'),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'job_offer' => ['required', 'string', 'max:50000'],
            'job_offer_html' => ['nullable', 'string', 'max:50000'],
        ], [], [
            'job_offer' => "l'offre d'emploi",
        ]);

        $file = $request->file('file');
        $jobOffer = (string) $request->input('job_offer');
        $jobOfferHtmlInput = (string) $request->input('job_offer_html', '');
        $jobOfferHtml = $this->jobOfferEditor->sanitizeHtml($jobOfferHtmlInput !== '' ? $jobOfferHtmlInput : $jobOffer);

        if (trim($jobOffer) === '') {
            $jobOffer = $this->jobOfferEditor->plainText('', $jobOfferHtml);
        }

        if (config('scan.ocr_async') && strtolower($file->getClientOriginalExtension()) === 'pdf') {
            $native = $this->extractor->extract((string) $file->getRealPath(), 'pdf', false);

            if (trim($native['text']) === '') {
                $stored = $file->storeAs('pending-ocr', uniqid('ocr-', true).'.pdf');
                OcrExtractJob::dispatch(
                    (string) $stored,
                    $file->getClientOriginalName(),
                    $jobOffer,
                    $jobOfferHtml,
                    Auth::id(),
                );

                return redirect()
                    ->route('scan.index')
                    ->with('scan.pending', __('scan.ocr_pending'))
                    ->with('scan.job_offer', $jobOffer)
                    ->with('scan.job_offer_html', $jobOfferHtml);
            }
        }

        try {
            $result = $this->runScan(
                path: (string) $file->getRealPath(),
                extension: strtolower($file->getClientOriginalExtension()),
                filename: $file->getClientOriginalName(),
                jobOffer: $jobOffer,
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('scan.index')
                ->with('scan.error', $e->getMessage())
                ->with('scan.job_offer', $jobOffer)
                ->with('scan.job_offer_html', $jobOfferHtml);
        }

        $scan = $this->persistScan($result, $jobOffer, $jobOfferHtml);

        return redirect()
            ->route('scan.index')
            ->with('scan.result', $result)
            ->with('scan.job_offer', $jobOffer)
            ->with('scan.job_offer_html', $jobOfferHtml)
            ->with('scan.id', $scan?->id);
    }

    public function api(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'job_offer' => ['required', 'string', 'max:50000'],
        ]);

        $file = $request->file('file');
        $jobOffer = (string) $request->input('job_offer');

        try {
            $result = $this->runScan(
                path: (string) $file->getRealPath(),
                extension: strtolower($file->getClientOriginalExtension()),
                filename: $file->getClientOriginalName(),
                jobOffer: $jobOffer,
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            return response()->json(['error' => "Erreur interne lors de l'analyse: ".$e->getMessage()], 500);
        }

        return response()->json($result);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function persistScan(array $result, string $jobOffer, string $jobOfferHtml): ?Scan
    {
        try {
            return Scan::create([
                'user_id' => Auth::id(),
                'filename' => (string) ($result['filename'] ?? 'cv'),
                'score' => (int) ($result['analysis']['global_score'] ?? 0),
                'ocr_used' => (bool) ($result['ocr_used'] ?? false),
                'job_offer' => mb_substr($jobOffer, 0, 50000),
                'job_offer_html' => $jobOfferHtml !== '' ? mb_substr($jobOfferHtml, 0, 50000) : null,
                'result' => $result,
                'content_hash' => hash('sha256', $jobOffer."\n".($result['filename'] ?? '')."\n".json_encode($result['analysis']['matched_skills'] ?? [])),
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $result
     */
    private function viewResult(?string $error = null, ?string $jobOffer = null, ?array $result = null, ?string $jobOfferHtml = null): View
    {
        return view('scan.index', [
            'templates' => $this->jobOfferEditor->templateLabels(),
            'templateContents' => $this->jobOfferEditor->templateContents(),
            'error' => $error,
            'jobOffer' => $jobOffer,
            'jobOfferHtml' => $jobOfferHtml,
            'result' => $result,
        ]);
    }

    /**
     * @return array{
     *     filename: string,
     *     ocr_used: bool,
     *     cv_text_preview: string,
     *     job_skills_found: list<string>,
     *     analysis: array<string, mixed>,
     *     processing_time_ms: int,
     * }
     */
    private function runScan(string $path, string $extension, string $filename, string $jobOffer): array
    {
        $startedAt = microtime(true);

        if (! in_array($extension, ['pdf', 'docx'], true)) {
            throw new RuntimeException('Formats acceptés : .pdf, .docx');
        }

        try {
            $extracted = $this->extractor->extract($path, $extension);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException('Erreur lors de l\'extraction du CV : '.$e->getMessage(), 0, $e);
        }

        $cvText = $extracted['text'];

        if (trim($cvText) === '') {
            throw new RuntimeException("Impossible d'extraire le texte du CV.");
        }

        $jobSkills = $this->skillLibrary->extractSkillsFromJob($jobOffer);
        $cacheKey = 'scan:'.hash('sha256', (string) file_get_contents($path)."\n".$jobOffer);

        $analysis = Cache::remember($cacheKey, now()->addHour(), function () use ($cvText, $jobSkills, $jobOffer) {
            return $this->analyzer->analyze($cvText, $jobSkills, $jobOffer);
        });

        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        $preview = mb_strlen($cvText) > 500 ? mb_substr($cvText, 0, 500).'...' : $cvText;
        $analysis['reformulation_suggestions'] = $this->reformulator->suggest($analysis);

        return [
            'filename' => $filename,
            'ocr_used' => $extracted['ocr_used'],
            'cv_text_preview' => $preview,
            'job_skills_found' => $jobSkills,
            'analysis' => $analysis,
            'processing_time_ms' => $elapsedMs,
        ];
    }
}
