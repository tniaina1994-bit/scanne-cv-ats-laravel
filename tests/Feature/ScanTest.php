<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScanTest extends TestCase
{
    private const SAMPLE_PDF = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data/CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.pdf';

    private const SAMPLE_DOCX = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data/CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.docx';

    private const DEVOPS_OFFER = "Ingénieur DevOps\n\nCompétences requises\n- Docker\n- Kubernetes\n- CI/CD\n- AWS\n- Linux\n- Terraform";

    public function test_scan_page_loads(): void
    {
        $this->get('/scan')->assertOk()->assertSee('Analyser un CV');
    }

    public function test_scan_requires_file(): void
    {
        $this->from('/scan')
            ->post('/scan', ['job_offer' => self::DEVOPS_OFFER])
            ->assertSessionHasErrors('file');
    }

    public function test_scan_requires_job_offer(): void
    {
        $this->from('/scan')
            ->post('/scan', [
                'file' => UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4'),
            ])
            ->assertSessionHasErrors('job_offer');
    }

    public function test_scan_rejects_disallowed_extension(): void
    {
        $this->from('/scan')
            ->post('/scan', [
                'file' => UploadedFile::fake()->createWithContent('cv.txt', 'not a cv'),
                'job_offer' => self::DEVOPS_OFFER,
            ])
            ->assertSessionHasErrors('file');
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
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_web_scan_analyzes_sample_pdf_against_devops_offer(): void
    {
        if (! is_file(self::SAMPLE_PDF)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $this->from('/scan')
            ->post('/scan', [
                'file' => new UploadedFile(self::SAMPLE_PDF, 'cv.pdf', 'application/pdf', null, true),
                'job_offer' => self::DEVOPS_OFFER,
            ])
            ->assertRedirect(route('scan.index'));

        $this->get(route('scan.index'))
            ->assertOk()
            ->assertSee('Score de compatibilité')
            ->assertSee('Compétences correspondantes', false)
            ->assertSee('DOCKER');
    }

    public function test_scan_result_is_cleared_on_refresh(): void
    {
        if (! is_file(self::SAMPLE_PDF)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $this->post('/scan', [
            'file' => new UploadedFile(self::SAMPLE_PDF, 'cv.pdf', 'application/pdf', null, true),
            'job_offer' => self::DEVOPS_OFFER,
        ])->assertRedirect(route('scan.index'));

        $this->get(route('scan.index'))->assertOk()->assertSee('Score de compatibilité');
        $this->get(route('scan.index'))->assertOk()->assertDontSee('Score de compatibilité');
    }

    public function test_scan_clear_button_is_available(): void
    {
        $this->get('/scan')
            ->assertOk()
            ->assertSee('Rejeter / Effacer tout', false)
            ->assertSee('id="clear-all"', false);
    }

    public function test_scan_preserves_job_offer_html_formatting(): void
    {
        if (! is_file(self::SAMPLE_PDF)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $this->post('/scan', [
            'file' => new UploadedFile(self::SAMPLE_PDF, 'cv.pdf', 'application/pdf', null, true),
            'job_offer' => "Ingénieur DevOps\nDocker Kubernetes Linux",
            'job_offer_html' => '<h2>Ingénieur DevOps</h2><ul><li>Docker</li><li>Kubernetes</li><li>Linux</li></ul>',
        ])->assertRedirect(route('scan.index'));

        $this->get(route('scan.index'))
            ->assertOk()
            ->assertSee('<h2>Ingénieur DevOps</h2>', false)
            ->assertSee('<li>Docker</li>', false)
            ->assertSee('id="job-offer-editor"', false)
            ->assertSee('name="job_offer_html"', false);
    }

    public function test_scan_support_it_template_keeps_formatting(): void
    {
        if (! is_file(self::SAMPLE_PDF)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $template = '<h2>Support Technique Informatique</h2><h3>Compétences requises</h3><ul><li>Windows Server</li><li>Linux</li></ul><h3>Missions</h3><ul><li>Installation</li></ul>';

        $this->post('/scan', [
            'file' => new UploadedFile(self::SAMPLE_PDF, 'cv.pdf', 'application/pdf', null, true),
            'job_offer' => 'Support Technique Informatique Windows Server Linux Installation',
            'job_offer_html' => $template,
        ])->assertRedirect(route('scan.index'));

        $body = $this->get(route('scan.index'))->getContent();

        $this->assertStringContainsString('<h2>Support Technique Informatique</h2>', $body);
        $this->assertStringContainsString('<h3>Compétences requises</h3>', $body);
        $this->assertStringContainsString('<li>Windows Server</li>', $body);
        $this->assertStringContainsString('<h3>Missions</h3>', $body);
    }

    public function test_scan_strips_dangerous_html_from_job_offer(): void
    {
        if (! is_file(self::SAMPLE_PDF)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $this->post('/scan', [
            'file' => new UploadedFile(self::SAMPLE_PDF, 'cv.pdf', 'application/pdf', null, true),
            'job_offer' => 'Linux Docker',
            'job_offer_html' => '<h2>Linux</h2><script>alert(1)</script><p onclick="x()">ok</p>',
        ])->assertRedirect(route('scan.index'));

        $html = $this->get(route('scan.index'))->getContent();

        $this->assertStringContainsString('<h2>Linux</h2>', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_api_scan_returns_scan_result_contract(): void
    {
        if (! is_file(self::SAMPLE_DOCX)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $response = $this->post('/api/scan', [
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
                    'personal_info' => ['name', 'email', 'phone', 'languages'],
                    'cv_skills_detected',
                    'matched_skills',
                    'matched_types',
                    'semantic_matches',
                    'missing_skills',
                    'ats_checks' => ['text_extractable', 'has_email', 'has_phone', 'has_sections'],
                    'experience_years',
                    'education_found',
                    'recommendations',
                ],
            ]);

        $analysis = $response->json('analysis');

        $this->assertContains('DOCKER', $response->json('job_skills_found'));
        $this->assertContains('LINUX', $analysis['matched_skills']);
        $this->assertSame('exact', $analysis['matched_types']['LINUX']);
        $this->assertContains('KUBERNETES', $analysis['missing_skills']);
        $this->assertGreaterThanOrEqual(0, $analysis['global_score']);
        $this->assertLessThanOrEqual(100, $analysis['global_score']);
    }
}
