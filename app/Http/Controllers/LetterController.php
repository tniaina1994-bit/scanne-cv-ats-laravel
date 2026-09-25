<?php

namespace App\Http\Controllers;

use App\Services\CoverLetterAnalyzer;
use App\Services\JobOfferEditor;
use App\Services\SkillLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cover letter vs job offer analysis (F-10).
 */
class LetterController extends Controller
{
    public function __construct(
        private readonly CoverLetterAnalyzer $analyzer,
        private readonly SkillLibrary $skillLibrary,
        private readonly JobOfferEditor $jobOfferEditor,
    ) {}

    public function index(): View
    {
        return view('letter.index', [
            'error' => session('letter.error'),
            'jobOffer' => session('letter.job_offer'),
            'jobOfferHtml' => session('letter.job_offer_html'),
            'letter' => session('letter.text'),
            'result' => session('letter.result'),
            'jobSkills' => session('letter.job_skills'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'job_offer' => ['required', 'string', 'max:50000'],
            'job_offer_html' => ['nullable', 'string', 'max:50000'],
            'letter' => ['required', 'string', 'min:80', 'max:30000'],
        ], [], [
            'job_offer' => "l'offre d'emploi",
            'letter' => 'la lettre',
        ]);

        $jobOffer = (string) $request->input('job_offer');
        $jobOfferHtmlInput = (string) $request->input('job_offer_html', '');
        $jobOfferHtml = $this->jobOfferEditor->sanitizeHtml($jobOfferHtmlInput !== '' ? $jobOfferHtmlInput : $jobOffer);
        $letter = trim((string) $request->input('letter'));

        if (trim($jobOffer) === '') {
            $jobOffer = $this->jobOfferEditor->plainText('', $jobOfferHtml);
        }

        if (trim($jobOffer) === '' || $letter === '') {
            return redirect()
                ->route('letter.index')
                ->with('letter.error', __('letter.error_empty'))
                ->with('letter.job_offer', $jobOffer)
                ->with('letter.job_offer_html', $jobOfferHtml)
                ->with('letter.text', $letter);
        }

        $jobSkills = $this->skillLibrary->extractSkillsFromJob($jobOffer);
        $result = $this->analyzer->analyze($letter, $jobOffer, $jobSkills);

        return redirect()
            ->route('letter.index')
            ->with('letter.result', $result)
            ->with('letter.job_offer', $jobOffer)
            ->with('letter.job_offer_html', $jobOfferHtml)
            ->with('letter.text', $letter)
            ->with('letter.job_skills', $jobSkills);
    }
}
