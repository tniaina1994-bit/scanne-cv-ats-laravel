<?php

namespace App\Http\Controllers;

use App\Services\CvAnalyzer;
use App\Services\DocumentTextExtractor;
use App\Services\JobOfferEditor;
use App\Services\ReformulationSuggester;
use App\Services\SkillLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CompareController extends Controller
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
        return view('compare.index', [
            'error' => session('compare.error'),
            'jobOffer' => session('compare.job_offer'),
            'jobOfferHtml' => session('compare.job_offer_html'),
            'templates' => $this->jobOfferEditor->templateLabels(),
            'templateContents' => $this->jobOfferEditor->templateContents(),
            'comparison' => session('compare.result'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file_a' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'file_b' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'job_offer' => ['required', 'string', 'max:50000'],
            'job_offer_html' => ['nullable', 'string', 'max:50000'],
        ], [], [
            'file_a' => 'CV A',
            'file_b' => 'CV B',
            'job_offer' => "l'offre d'emploi",
        ]);

        $jobOfferHtmlInput = (string) $request->input('job_offer_html', '');
        $jobOfferHtml = $this->jobOfferEditor->sanitizeHtml(
            $jobOfferHtmlInput !== '' ? $jobOfferHtmlInput : (string) $request->input('job_offer', '')
        );
        $jobOffer = $this->jobOfferEditor->plainText((string) $request->input('job_offer', ''), $jobOfferHtml);

        try {
            $jobSkills = $this->skillLibrary->extractSkillsFromJob($jobOffer);
            $sideA = $this->analyzeFile($request->file('file_a'), $jobSkills);
            $sideB = $this->analyzeFile($request->file('file_b'), $jobSkills);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('compare.index')
                ->with('compare.error', $e->getMessage())
                ->with('compare.job_offer', $jobOffer)
                ->with('compare.job_offer_html', $jobOfferHtml);
        }

        return redirect()
            ->route('compare.index')
            ->with('compare.job_offer', $jobOffer)
            ->with('compare.job_offer_html', $jobOfferHtml)
            ->with('compare.result', [
                'job_skills_found' => $jobSkills,
                'a' => $sideA,
                'b' => $sideB,
            ]);
    }

    /**
     * @param  list<string>  $jobSkills
     * @return array<string, mixed>
     */
    private function analyzeFile(mixed $file, array $jobSkills): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['pdf', 'docx'], true)) {
            throw new RuntimeException('Formats acceptés : .pdf, .docx');
        }

        try {
            $extracted = $this->extractor->extract((string) $file->getRealPath(), $extension);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException("Erreur lors de l'extraction : ".$e->getMessage(), 0, $e);
        }

        if (trim($extracted['text']) === '') {
            throw new RuntimeException("Impossible d'extraire le texte d'un des CV.");
        }

        $analysis = $this->analyzer->analyze($extracted['text'], $jobSkills);
        $analysis['reformulation_suggestions'] = $this->reformulator->suggest($analysis);

        return [
            'filename' => $file->getClientOriginalName(),
            'ocr_used' => $extracted['ocr_used'],
            'analysis' => $analysis,
        ];
    }
}
