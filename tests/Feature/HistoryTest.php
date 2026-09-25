<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_page_loads(): void
    {
        $this->get(route('history.index'))
            ->assertOk()
            ->assertSee(__('history.title'));
    }

    public function test_history_lists_scans(): void
    {
        $scan = Scan::factory()->create(['filename' => 'mon-cv.pdf']);

        $this->get(route('history.index'))
            ->assertOk()
            ->assertSee('mon-cv.pdf');
    }

    public function test_history_show_displays_score(): void
    {
        $scan = Scan::factory()->create(['score' => 72]);

        $this->get(route('history.show', $scan))
            ->assertOk()
            ->assertSee('72%')
            ->assertSee('Score de compatibilité');
    }

    public function test_history_export_csv(): void
    {
        $scan = Scan::factory()->create(['score' => 55, 'filename' => 'cv.pdf']);

        $response = $this->get(route('history.export', $scan));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Score global', $response->streamedContent());
        $this->assertStringContainsString('55', $response->streamedContent());
    }

    public function test_history_delete(): void
    {
        $scan = Scan::factory()->create();

        $this->delete(route('history.destroy', $scan))
            ->assertRedirect(route('history.index'));

        $this->assertDatabaseMissing('scans', ['id' => $scan->id]);
    }

    public function test_signed_share_link_works(): void
    {
        $scan = Scan::factory()->create();

        $url = URL::signedRoute('history.share', $scan);

        $this->get($url)
            ->assertOk()
            ->assertSee('partage', false);
    }

    public function test_unsigned_share_link_is_rejected(): void
    {
        $scan = Scan::factory()->create();

        $this->get(route('history.share', $scan))
            ->assertForbidden();
    }

    public function test_private_scan_hidden_from_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $scan = Scan::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('history.show', $scan))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('history.show', $scan))
            ->assertOk();
    }
}
