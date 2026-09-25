<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ScanApiTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_PDF = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data/CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.pdf';

    private const SAMPLE_DOCX = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data/CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.docx';

    private const DEVOPS_OFFER = "Ingénieur DevOps\n\nCompétences requises\n- Docker\n- Kubernetes\n- CI/CD\n- AWS\n- Linux\n- Terraform";

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('api-scan|127.0.0.1');
        Cache::flush();
    }

    public function test_api_scan_requires_file_and_job_offer(): void
    {
        $this->postJson('/api/scan', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file', 'job_offer']);
    }

    public function test_api_scan_rejects_disallowed_extension(): void
    {
        $this->postJson('/api/scan', [
            'file' => UploadedFile::fake()->createWithContent('cv.txt', 'not a cv'),
            'job_offer' => self::DEVOPS_OFFER,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_api_scan_returns_full_contract(): void
    {
        if (! is_file(self::SAMPLE_DOCX)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $response = $this->postJson('/api/scan', [
            'file' => new UploadedFile(self::SAMPLE_DOCX, 'cv.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
            'job_offer' => self::DEVOPS_OFFER,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'filename',
                'ocr_used',
                'cv_text_preview',
                'job_skills_found',
                'processing_time_ms',
                'analysis' => [
                    'global_score',
                    'scores' => ['skills', 'experience', 'education', 'ats_quality'],
                    'personal_info',
                    'matched_skills',
                    'missing_skills',
                    'recommendations',
                    'reformulation_suggestions',
                ],
            ]);

        $score = $response->json('analysis.global_score');
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_api_scan_returns_400_on_extraction_failure(): void
    {
        $this->postJson('/api/scan', [
            'file' => UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 not really a pdf'),
            'job_offer' => self::DEVOPS_OFFER,
        ])->assertStatus(400)
            ->assertJsonStructure(['error']);
    }

    public function test_api_scan_is_rate_limited_after_ten_requests(): void
    {
        if (! is_file(self::SAMPLE_DOCX)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $payload = [
            'file' => new UploadedFile(self::SAMPLE_DOCX, 'cv.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
            'job_offer' => self::DEVOPS_OFFER,
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/scan', $payload)->assertOk();
        }

        $this->postJson('/api/scan', $payload)->assertStatus(429);
    }

    public function test_api_scan_result_is_cached_on_second_call(): void
    {
        if (! is_file(self::SAMPLE_DOCX)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $payload = [
            'file' => new UploadedFile(self::SAMPLE_DOCX, 'cv.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
            'job_offer' => self::DEVOPS_OFFER,
        ];

        $first = $this->postJson('/api/scan', $payload)->assertOk();
        $second = $this->postJson('/api/scan', $payload)->assertOk();

        $this->assertSame(
            $first->json('analysis.global_score'),
            $second->json('analysis.global_score')
        );
        $this->assertSame(
            $first->json('analysis.matched_skills'),
            $second->json('analysis.matched_skills')
        );
    }
}
