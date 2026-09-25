<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CompareTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_PDF = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data/CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.pdf';

    private const DEVOPS_OFFER = "Ingénieur DevOps\nDocker\nLinux\nTerraform";

    public function test_compare_page_loads(): void
    {
        $this->get(route('compare.index'))
            ->assertOk()
            ->assertSee('Comparer 2 CV')
            ->assertSee('name="file_a"', false)
            ->assertSee('name="file_b"', false)
            ->assertSee('id="job-offer-editor"', false)
            ->assertSee('name="job_offer"', false)
            ->assertSee('data-template="devops"', false);
    }

    public function test_compare_requires_two_files_and_offer(): void
    {
        $this->from(route('compare.index'))
            ->post(route('compare.store'), [])
            ->assertSessionHasErrors(['file_a', 'file_b', 'job_offer']);
    }

    public function test_compare_rejects_invalid_file_types(): void
    {
        $this->from(route('compare.index'))
            ->post(route('compare.store'), [
                'file_a' => UploadedFile::fake()->createWithContent('a.txt', 'x'),
                'file_b' => UploadedFile::fake()->createWithContent('b.txt', 'y'),
                'job_offer' => self::DEVOPS_OFFER,
            ])
            ->assertSessionHasErrors(['file_a', 'file_b']);
    }

    public function test_compare_shows_scores_for_both_cvs(): void
    {
        if (! is_file(self::SAMPLE_PDF)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $make = fn () => new UploadedFile(self::SAMPLE_PDF, 'cv.pdf', 'application/pdf', null, true);

        $this->from(route('compare.index'))
            ->post(route('compare.store'), [
                'file_a' => $make(),
                'file_b' => $make(),
                'job_offer' => self::DEVOPS_OFFER,
            ])
            ->assertRedirect(route('compare.index'));

        $this->get(route('compare.index'))
            ->assertOk()
            ->assertSee('Écart')
            ->assertSee('CV A')
            ->assertSee('CV B')
            ->assertSee('%');
    }

    public function test_compare_theme_toggle_present(): void
    {
        $this->get(route('compare.index'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false);
    }

    public function test_compare_preserves_job_offer_html_formatting(): void
    {
        if (! is_file(self::SAMPLE_PDF)) {
            $this->markTestSkipped('Sample CV not available.');
        }

        $make = fn () => new UploadedFile(self::SAMPLE_PDF, 'cv.pdf', 'application/pdf', null, true);

        $this->from(route('compare.index'))
            ->post(route('compare.store'), [
                'file_a' => $make(),
                'file_b' => $make(),
                'job_offer' => self::DEVOPS_OFFER,
                'job_offer_html' => '<h2>Ingénieur DevOps</h2><ul><li>Docker</li><li>Linux</li></ul>',
            ])
            ->assertRedirect(route('compare.index'));

        $this->get(route('compare.index'))
            ->assertOk()
            ->assertSee('<h2>Ingénieur DevOps</h2>', false)
            ->assertSee('<li>Docker</li>', false)
            ->assertSee('id="job-offer-editor"', false);
    }
}
