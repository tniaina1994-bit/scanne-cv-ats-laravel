<?php

namespace Tests\Unit;

use App\Services\CoverLetterAnalyzer;
use App\Services\CvAnalyzer;
use App\Services\CvSectionDetector;
use App\Services\JobOfferRequirements;
use App\Services\PersonalInfoExtractor;
use App\Services\ReformulationSuggester;
use App\Services\SkillLibrary;
use App\Services\TfIdfSemanticMatcher;
use PHPUnit\Framework\TestCase;

class AdvancedAnalysisTest extends TestCase
{
    public function test_section_detector_finds_fr_headers(): void
    {
        $detector = new CvSectionDetector;
        $cv = "Jean Dupont\njean@example.com\n\nProfil\nDéveloppeur motivé.\n\nExpérience\n3 ans chez ACME.\n\nCompétences\nPHP, Docker\n\nFormation\nLicence";

        $this->assertTrue($detector->hasStructuredSections($cv));
        $keys = $detector->foundSections($cv);
        $this->assertContains('profile', $keys);
        $this->assertContains('experience', $keys);
        $this->assertContains('skills', $keys);
        $this->assertContains('education', $keys);

        $sections = $detector->detect($cv);
        $this->assertStringContainsString('Développeur motivé', $sections['profile']);
        $this->assertStringContainsString('3 ans chez ACME', $sections['experience']);
    }

    public function test_section_detector_handles_unstructured_cv(): void
    {
        $detector = new CvSectionDetector;
        $cv = 'Juste un paragraphe sans titres reconnus.';

        $this->assertFalse($detector->hasStructuredSections($cv));
        $this->assertSame([], $detector->foundSections($cv));
    }

    public function test_job_offer_requirements_parses_years_and_languages(): void
    {
        $req = new JobOfferRequirements;

        $result = $req->extract("Offre: 3 ans d'expérience minimum en dev. Anglais et français requis.");

        $this->assertSame(3, $result['experience_required']);
        $this->assertContains('anglais', $result['languages']);
        $this->assertContains('français', $result['languages']);
    }

    public function test_analyzer_reports_semantic_score_and_sections(): void
    {
        $analyzer = new CvAnalyzer(
            new SkillLibrary,
            new TfIdfSemanticMatcher,
            new PersonalInfoExtractor,
        );

        $cv = "Jean Dupont jean@example.com +33612345678\n\nExpérience: 5 ans\n\nCompétences\n- Python\n- Docker\n- Linux\n\nFormation\nMaster Informatique";
        $analysis = $analyzer->analyze($cv, ['PYTHON', 'DOCKER', 'KUBERNETES'], "Poste: 3 ans d'expérience requises");

        $this->assertArrayHasKey('semantic', $analysis['scores']);
        $this->assertContains('experience', $analysis['section_keys']);
        $this->assertSame(3, $analysis['experience_required']);
        $this->assertSame(0, $analysis['experience_gap']);
        // 67*0.5 + 100*0.2 + 80*0.1 + 100*0.2 ≈ 83.5 → with exact 2/3: round(66.67)=67
        $this->assertArrayHasKey('weights', $analysis);
        $this->assertSame(0.0, $analysis['weights']['semantic']);
        $this->assertContains('PYTHON', $analysis['matched_skills']);
        $this->assertContains('KUBERNETES', $analysis['missing_skills']);
    }

    public function test_analyzer_experience_gap_recommendation(): void
    {
        $analyzer = new CvAnalyzer(
            new SkillLibrary,
            new TfIdfSemanticMatcher,
            new PersonalInfoExtractor,
        );

        $cv = "Contact: a@b.com 0612345678\n\nExpérience: 1 an\n\nCompétences: PHP\n\nFormation licence";
        $analysis = $analyzer->analyze($cv, ['PHP'], "Recherche: 5 ans d'expérience");

        $this->assertSame(5, $analysis['experience_required']);
        $this->assertSame(4, $analysis['experience_gap']);
        $found = false;

        foreach ($analysis['recommendations'] as $recommendation) {
            if (str_contains($recommendation, 'Experience requise')) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    public function test_weights_default_keep_score_formula(): void
    {
        $analyzer = new CvAnalyzer(
            new SkillLibrary,
            new TfIdfSemanticMatcher,
            new PersonalInfoExtractor,
        );

        $cv = "a@b.com 0612345678\nExpérience: 5 ans\nCompétences: Python\nFormation master";
        $analysis = $analyzer->analyze($cv, ['PYTHON']);

        $this->assertSame(0.5, $analysis['weights']['skills']);
        $this->assertSame(0.0, $analysis['weights']['semantic']);
    }

    public function test_cover_letter_analyzer_scores_and_suggestions(): void
    {
        $analyzer = new CoverLetterAnalyzer(new SkillLibrary, new TfIdfSemanticMatcher);

        $job = "Poste développeur: Python et Docker. 3 ans d'expérience.";
        $jobSkills = (new SkillLibrary)->extractSkillsFromJob($job);

        $good = "Madame, Monsieur,\n\n".str_repeat(
            'Je propose mon expertise Python et Docker pour ce poste, avec des résultats concrets sur des projets cloud et containers. ',
            5
        )."\nCordialement";

        $result = $analyzer->analyze($good, $job, $jobSkills);

        $this->assertGreaterThanOrEqual(40, $result['global_score']);
        $this->assertTrue($result['checks']['has_greeting']);
        $this->assertTrue($result['checks']['has_closing']);
        $this->assertContains('DOCKER', $result['matched_skills']);
        $this->assertIsInt($result['word_count']);

        $bad = 'ok';
        $weak = $analyzer->analyze($bad, $job, $jobSkills);
        $this->assertFalse($weak['checks']['has_greeting']);
        $this->assertFalse($weak['checks']['has_closing']);
        $this->assertNotEmpty($weak['suggestions']);
    }

    public function test_reformulation_suggester_handles_experience_gap(): void
    {
        $suggester = new ReformulationSuggester;

        $suggestions = $suggester->suggest([
            'experience_years' => 1,
            'experience_required' => 5,
            'experience_gap' => 4,
            'missing_skills' => ['PYTHON'],
            'ats_checks' => ['has_email' => true, 'has_phone' => true, 'has_sections' => false],
            'recommendations' => [],
            'section_keys' => [],
            'education_found' => [],
            'scores' => ['education' => 0, 'ats_quality' => 40],
        ]);

        $joined = implode(' | ', $suggestions);
        $this->assertStringContainsString('Réécrivez votre expérience', $joined);
        $this->assertStringContainsString('PYTHON', $joined);
        $this->assertStringContainsString('Structurez le CV', $joined);
        $this->assertStringContainsString('Formation : reformulez', $joined);
    }

    public function test_multi_lang_aliases_normalize_skills(): void
    {
        $library = new SkillLibrary;

        $this->assertSame('docker', $library->normalizeSkill('conteneur'));
        $this->assertSame('git', $library->normalizeSkill('gestion de version'));
    }
}
