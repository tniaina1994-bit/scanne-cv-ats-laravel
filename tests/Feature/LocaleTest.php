<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-theme-toggle', false);
    }

    public function test_lang_query_switches_to_english_chrome(): void
    {
        $this->get('/?lang=en')
            ->assertOk()
            ->assertSee(__('home.scan_cta', [], 'en'), false)
            ->assertSee('Scan a CV', false);
    }

    public function test_lang_query_switches_to_french_chrome(): void
    {
        $this->get('/?lang=en');
        $this->get('/?lang=fr')
            ->assertOk()
            ->assertSee('Scanner un CV', false);
    }
}
