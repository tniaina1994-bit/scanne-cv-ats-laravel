<?php

namespace Tests\Unit;

use App\Services\CvAnalyzer;
use App\Services\PersonalInfoExtractor;
use App\Services\SkillLibrary;
use App\Services\TfIdfSemanticMatcher;
use PHPUnit\Framework\TestCase;

class CvAnalyzerTest extends TestCase
{
    private CvAnalyzer $analyzer;

    private SkillLibrary $skills;

    private TfIdfSemanticMatcher $semanticMatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skills = new SkillLibrary;
        $this->semanticMatcher = new TfIdfSemanticMatcher;
        $this->analyzer = new CvAnalyzer(
            $this->skills,
            $this->semanticMatcher,
            new PersonalInfoExtractor,
        );
    }

    public function test_loads_synonyms_from_resources(): void
    {
        $this->assertArrayHasKey('javascript', $this->skills->synonyms());
        $this->assertSame('node.js', $this->skills->normalizeSkill('nodejs'));
        $this->assertSame('kubernetes', $this->skills->normalizeSkill('K8S'));
    }

    public function test_extracts_job_skills_from_offer(): void
    {
        $skills = $this->skills->extractSkillsFromJob('Compétences: Python, Docker et Kubernetes requis. Experience CI/CD.');

        $this->assertContains('PYTHON', $skills);
        $this->assertContains('DOCKER', $skills);
        $this->assertContains('KUBERNETES', $skills);
        $this->assertNotContains('FIGMA', $skills);
    }

    public function test_exact_match_scores_and_missing_skills(): void
    {
        $cv = "Jean Dupont\n jean@example.com\n +33612345678\n\nExpérience: 5 ans\n\nCompétences\n- Python\n- Docker\n- Linux\n\nFormation\nMaster Informatique";

        $jobSkills = ['PYTHON', 'DOCKER', 'KUBERNETES', 'TERRAFORM'];
        $analysis = $this->analyzer->analyze($cv, $jobSkills);

        $this->assertContains('PYTHON', $analysis['matched_skills']);
        $this->assertSame('exact', $analysis['matched_types']['PYTHON']);
        $this->assertContains('KUBERNETES', $analysis['missing_skills']);
        $this->assertNotContains('KUBERNETES', $analysis['matched_skills']);
        $this->assertSame(50, $analysis['scores']['skills']);
        $this->assertSame(5, $analysis['experience_years']);
        $this->assertSame(100, $analysis['scores']['experience']);
        $this->assertContains('master', $analysis['education_found']);
        $this->assertSame(80, $analysis['scores']['education']);
        $this->assertTrue($analysis['ats_checks']['has_email']);
        $this->assertTrue($analysis['ats_checks']['has_phone']);
        $this->assertTrue($analysis['ats_checks']['has_sections']);
        $this->assertSame(100, $analysis['scores']['ats_quality']);
        // 50*0.5 + 100*0.2 + 80*0.1 + 100*0.2 = 25 + 20 + 8 + 20 = 73
        $this->assertSame(73, $analysis['global_score']);
        $this->assertSame('Jean Dupont', $analysis['personal_info']['name']);
        $this->assertSame('jean@example.com', $analysis['personal_info']['email']);
    }

    public function test_synonym_match_via_synonyms_json(): void
    {
        $cv = "CV\ncontact@x.com 0612345678\n\nExpérience: 3 ans\n\nCompétences: nodejs, react, javascript\n\nFormation licence";

        $analysis = $this->analyzer->analyze($cv, ['NODE.JS']);

        $this->assertContains('NODE.JS', $analysis['matched_skills']);
        $this->assertSame('synonym(nodejs)', $analysis['matched_types']['NODE.JS']);
        $this->assertSame([], $analysis['missing_skills']);
    }

    public function test_recommendations_flag_missing_contact_and_skills(): void
    {
        $cv = 'Quelques mots sans structure ni email.';

        $analysis = $this->analyzer->analyze($cv, ['PYTHON', 'DOCKER']);

        $this->assertContains('Ajoutez votre email', $analysis['recommendations']);
        $this->assertContains('Ajoutez votre telephone', $analysis['recommendations']);
        $this->assertContains('Structurez votre CV avec des sections', $analysis['recommendations']);

        $recommendation = collect($analysis['recommendations'])->first(
            static fn (string $r): bool => str_starts_with($r, 'Competences manquantes')
        );
        $this->assertNotNull($recommendation);
        $this->assertStringContainsString('PYTHON', $recommendation);
    }

    public function test_semantic_matcher_scores_shared_vocabulary(): void
    {
        // "data" and "engineer" never appear as the exact adjacent phrase, so lexical
        // levels fail, but both unigrams occur in the CV → TF-IDF cosine should fire.
        $cv = "Profil data oriente cloud\nemail contact@pro.com 0600000000\n\nCompetences: data, etl, python\n\nFormation ingenieur master";

        $results = $this->semanticMatcher->match(['DATA ENGINEER'], $cv, []);

        $this->assertNotEmpty($results);
        $this->assertSame('DATA ENGINEER', $results[0]['skill']);
        $this->assertSame('semantic', $results[0]['match_type']);
        $this->assertGreaterThan(0.15, $results[0]['similarity']);
    }

    public function test_semantic_matcher_skips_already_matched_and_unrelated_skills(): void
    {
        $cv = 'Experience uniquement en gestion de projet.';

        $this->assertSame([], $this->semanticMatcher->match(['PYTHON'], $cv, ['PYTHON']));
        $this->assertSame([], $this->semanticMatcher->match(['QUANTUMFLUX'], $cv, []));
        $this->assertSame([], $this->semanticMatcher->match(['PYTHON'], '   ', []));
    }

    public function test_analyzer_folds_semantic_hits_into_matched_skills(): void
    {
        // Tokens "machine" and "learning" both appear, never as the adjacent phrase
        // and with no partial/synonym bridge → multi-level fails, TF-IDF recovers.
        $cv = "Machine configuree en datacenter\nemail a@b.com 0611111111\n\nCompetences: learning, python\n\nFormation master";

        $analysis = $this->analyzer->analyze($cv, ['MACHINE LEARNING']);

        $this->assertContains('MACHINE LEARNING', $analysis['matched_skills']);
        $this->assertStringStartsWith('semantic(', $analysis['matched_types']['MACHINE LEARNING']);
        $this->assertNotEmpty($analysis['semantic_matches']);
        $this->assertSame([], $analysis['missing_skills']);
    }

    public function test_multi_level_match_levels(): void
    {
        [$matchedExact, $typeExact] = $this->skills->multiLevelMatch('react', 'utilisation de reactjs et docker desktop', ['REACTJS']);
        $this->assertTrue($matchedExact);
        $this->assertStringStartsWith('synonym(', $typeExact);

        [$matchedRelated, $typeRelated] = $this->skills->multiLevelMatch('kubernetes', 'utilisation de reactjs et docker desktop', ['K8S']);
        $this->assertTrue($matchedRelated);
        $this->assertStringStartsWith('related(', $typeRelated);

        [$matchedPartial, $typePartial] = $this->skills->multiLevelMatch('react', 'projet reactnative en cours', ['REACTNATIVE']);
        $this->assertTrue($matchedPartial);
        $this->assertStringStartsWith('partial(', $typePartial);

        [$matchedNone] = $this->skills->multiLevelMatch('terraform', 'utilisation de reactjs et docker desktop', ['PYTHON']);
        $this->assertFalse($matchedNone);
    }

    public function test_extracts_multi_label_website_and_skips_social(): void
    {
        $extractor = new PersonalInfoExtractor;

        $info = $extractor->extract("Jean Dupont\nSite web: valeur1.domaine.com\nContact jean@exemple.fr");
        $this->assertSame('valeur1.domaine.com', $info['website']);

        $info = $extractor->extract(
            "Profil\nhttps://github.com/jdoe\nlinkedin.com/in/jdoe\nSite: https://valeur1.domaine.com/portfolio"
        );
        $this->assertSame('https://valeur1.domaine.com/portfolio', $info['website']);

        $info = $extractor->extract('Uniquement linkedin.com/in/jdoe et github.com/jdoe');
        $this->assertSame('', $info['website']);
    }
}
