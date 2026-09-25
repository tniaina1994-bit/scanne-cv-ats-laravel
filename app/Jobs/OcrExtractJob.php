<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Services\CvAnalyzer;
use App\Services\DocumentTextExtractor;
use App\Services\ReformulationSuggester;
use App\Services\SkillLibrary;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class OcrExtractJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(
        public readonly string $path,
        public readonly string $filename,
        public readonly string $jobOffer,
        public readonly string $jobOfferHtml,
        public readonly ?int $userId,
    ) {}

    public function handle(
        DocumentTextExtractor $extractor,
        SkillLibrary $skillLibrary,
        CvAnalyzer $analyzer,
        ReformulationSuggester $reformulator,
    ): void {
        try {
            $extracted = $extractor->extract($this->path, 'pdf', true);

            if (trim($extracted['text']) === '') {
                throw new RuntimeException('OCR n\'a extrait aucun texte du PDF.');
            }

            $jobSkills = $skillLibrary->extractSkillsFromJob($this->jobOffer);
            $analysis = $analyzer->analyze($extracted['text'], $jobSkills);
            $analysis['reformulation_suggestions'] = $reformulator->suggest($analysis);

            $result = [
                'filename' => $this->filename,
                'ocr_used' => true,
                'cv_text_preview' => mb_substr($extracted['text'], 0, 500),
                'job_skills_found' => $jobSkills,
                'analysis' => $analysis,
                'processing_time_ms' => 0,
            ];

            Scan::create([
                'user_id' => $this->userId,
                'filename' => $this->filename,
                'score' => (int) ($analysis['global_score'] ?? 0),
                'ocr_used' => true,
                'job_offer' => mb_substr($this->jobOffer, 0, 50000),
                'job_offer_html' => $this->jobOfferHtml !== '' ? mb_substr($this->jobOfferHtml, 0, 50000) : null,
                'result' => $result,
                'content_hash' => hash('sha256', $this->jobOffer."\n".$this->filename),
            ]);
        } catch (Throwable $e) {
            Log::error('OCR job failed', ['error' => $e->getMessage(), 'path' => $this->path]);

            throw $e;
        } finally {
            Storage::disk('local')->delete($this->path);
        }
    }
}
