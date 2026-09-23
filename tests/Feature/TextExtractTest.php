<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TextExtractTest extends TestCase
{
    public function test_extract_page_loads(): void
    {
        $this->get('/extract')
            ->assertOk()
            ->assertSee('Test extraction')
            ->assertSee('Rejeter / Effacer tout', false);
    }

    public function test_extract_requires_file(): void
    {
        $this->from('/extract')
            ->post('/extract', [])
            ->assertSessionHasErrors('file');
    }

    public function test_extract_rejects_disallowed_extension(): void
    {
        $this->from('/extract')
            ->post('/extract', [
                'file' => UploadedFile::fake()->createWithContent('cv.txt', 'not a cv'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_extract_reads_sample_pdf_when_available(): void
    {
        $sample = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data/CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.pdf';

        if (! is_file($sample)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $this->from('/extract')
            ->post('/extract', [
                'file' => new UploadedFile($sample, 'cv.pdf', 'application/pdf', null, true),
            ])
            ->assertRedirect(route('extract.index'));

        $this->get(route('extract.index'))
            ->assertOk()
            ->assertSee('smalot/pdfparser', false)
            ->assertSee('RAKOTONJARY', false);

        $this->get(route('extract.index'))
            ->assertOk()
            ->assertDontSee('smalot/pdfparser', false);
    }

    public function test_extract_sample_endpoint_reads_docx(): void
    {
        $sample = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data/CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.docx';

        if (! is_file($sample)) {
            $this->markTestSkipped('Sample DOCX not available.');
        }

        $this->from('/extract')
            ->post('/extract/sample', [
                'sample' => 'CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.docx',
            ])
            ->assertRedirect(route('extract.index'));

        $this->get(route('extract.index'))
            ->assertOk()
            ->assertSee('unzip+xml', false);
    }

    public function test_extract_rejects_unknown_sample(): void
    {
        $this->from('/extract')
            ->post('/extract/sample', [
                'sample' => '../../etc/passwd',
            ])
            ->assertRedirect(route('extract.index'));

        $this->get(route('extract.index'))
            ->assertOk()
            ->assertSee('Exemple inconnu.');
    }
}
