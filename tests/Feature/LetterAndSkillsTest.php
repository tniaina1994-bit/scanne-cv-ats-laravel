<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use App\Services\SkillLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LetterAndSkillsTest extends TestCase
{
    use RefreshDatabase;

    public function test_letter_page_loads(): void
    {
        $this->get('/letter')->assertOk()->assertSee(__('letter.title'));
    }

    public function test_letter_analysis_requires_fields(): void
    {
        $this->post('/letter', [
            'job_offer' => '',
            'letter' => '',
        ])->assertSessionHasErrors(['job_offer', 'letter']);
    }

    public function test_letter_analysis_returns_result(): void
    {
        $job = "Développeur Python et Docker, 3 ans d'expérience minimum.";
        $letter = "Madame, Monsieur,\n\n".str_repeat(
            'Mon expertise Python et Docker me permettra de contribuer rapidement à vos projets cloud et conteneurs avec des livrables mesurables. ',
            4
        )."\nCordialement";

        $this->post('/letter', [
            'job_offer' => $job,
            'letter' => $letter,
        ])
            ->assertRedirect('/letter')
            ->assertSessionHas('letter.result');

        $result = session('letter.result');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('global_score', $result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertTrue($result['checks']['has_greeting']);

        $this->get('/letter')->assertOk()->assertSee((string) $result['global_score'].'%', false);
    }

    public function test_admin_skills_requires_auth(): void
    {
        $this->get('/admin/skills')->assertRedirect('/login');
    }

    public function test_admin_can_create_update_delete_skill(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/admin/skills', [
                'name' => 'Flutterflow',
                'category' => 'mobile',
                'synonyms' => 'no-code, visual builder',
            ])
            ->assertRedirect('/admin/skills')
            ->assertSessionHas('status', __('skills.created'));

        $skill = Skill::where('name', 'flutterflow')->firstOrFail();
        $this->assertSame(['no-code', 'visual builder'], $skill->synonyms);

        $library = app(SkillLibrary::class);
        $this->assertArrayHasKey('flutterflow', $library->synonyms());
        $this->assertSame('flutterflow', $library->normalizeSkill('no-code'));

        $this->actingAs($user)
            ->put('/admin/skills/'.$skill->id, [
                'name' => 'Flutterflow',
                'category' => 'mobile+',
                'synonyms' => 'no-code',
            ])
            ->assertRedirect('/admin/skills')
            ->assertSessionHas('status', __('skills.updated'));

        $skill->refresh();
        $this->assertSame('mobile+', $skill->category);

        $this->actingAs($user)
            ->delete('/admin/skills/'.$skill->id)
            ->assertRedirect('/admin/skills')
            ->assertSessionHas('status', __('skills.deleted'));

        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    }

    public function test_scan_analysis_exposes_semantic_and_sections(): void
    {
        $cvPath = base_path('../ProjetATS/ats-cv-analyzer/data/cv_simple.pdf');

        if (! is_file($cvPath)) {
            $this->markTestSkipped('Sample CV missing.');
        }

        $this->post('/scan', [
            'file' => $this->getPath($cvPath, 'cv_simple.pdf'),
            'job_offer' => "Offre développeur: Python, Docker. 3 ans d'expérience.",
        ], ['Accept' => 'application/json'])
            ->assertSessionHas('scan.result');

        $result = session('scan.result');
        $this->assertArrayHasKey('semantic', $result['analysis']['scores']);
        $this->assertArrayHasKey('section_keys', $result['analysis']);
        $this->assertArrayHasKey('experience_required', $result['analysis']);
        $this->assertArrayHasKey('weights', $result['analysis']);
    }

    private function getPath(string $absolute, string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            (string) file_get_contents($absolute)
        );
    }
}
