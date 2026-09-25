<?php

namespace Tests\Feature;

use App\Jobs\OcrExtractJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OcrQueueTest extends TestCase
{
    use RefreshDatabase;

    private const BLANK_PDF = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
        ."2 0 obj << /Type /Pages /Kids [] /Count 0 >> endobj\n"
        ."trailer << /Root 1 0 R >>\n%%EOF\n";

    public function test_blank_pdf_ocrs_sync_by_default_without_queue(): void
    {
        Queue::fake();
        config(['scan.ocr_async' => false]);

        $this->from(route('scan.index'))
            ->post(route('scan.store'), [
                'file' => UploadedFile::fake()->createWithContent('scanned.pdf', self::BLANK_PDF),
                'job_offer' => "Offre DevOps\nDocker\nLinux",
                'job_offer_html' => '<p>Offre DevOps</p>',
            ])
            ->assertRedirect(route('scan.index'))
            ->assertSessionHas('scan.error')
            ->assertSessionMissing('scan.pending');

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('scans', 0);
    }

    public function test_blank_pdf_is_queued_only_when_async_enabled(): void
    {
        Queue::fake();
        config(['scan.ocr_async' => true]);

        $this->from(route('scan.index'))
            ->post(route('scan.store'), [
                'file' => UploadedFile::fake()->createWithContent('scanned.pdf', self::BLANK_PDF),
                'job_offer' => "Offre DevOps\nDocker\nLinux",
                'job_offer_html' => '<p>Offre DevOps</p>',
            ])
            ->assertRedirect(route('scan.index'))
            ->assertSessionHas('scan.pending');

        Queue::assertPushed(OcrExtractJob::class, 1);
    }

    public function test_scan_page_shows_ocr_pending_flash_with_history_link(): void
    {
        $this->get(route('scan.index'))
            ->assertOk()
            ->assertDontSee('OCR en cours', false);

        $this->withSession(['scan.pending' => __('scan.ocr_pending')])
            ->get(route('scan.index'))
            ->assertOk()
            ->assertSee('OCR en cours', false)
            ->assertSee(route('history.index'), false)
            ->assertSee(__('scan.view_history'));
    }

    public function test_native_text_pdf_does_not_queue_ocr(): void
    {
        Queue::fake();
        config(['scan.ocr_async' => true]);

        $pdf = $this->samplePdfPath();

        if ($pdf === null) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $this->from(route('scan.index'))
            ->post(route('scan.store'), [
                'file' => UploadedFile::fake()->createWithContent('cv.pdf', (string) file_get_contents($pdf)),
                'job_offer' => "Offre Support\nWindows Server\nLinux",
            ])
            ->assertRedirect(route('scan.index'))
            ->assertSessionHas('scan.result')
            ->assertSessionMissing('scan.pending');

        Queue::assertNothingPushed();
    }

    private function samplePdfPath(): ?string
    {
        $dir = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data';

        if (! is_dir($dir)) {
            return null;
        }

        foreach (glob($dir.'/*.pdf') ?: [] as $path) {
            return $path;
        }

        return null;
    }
}
