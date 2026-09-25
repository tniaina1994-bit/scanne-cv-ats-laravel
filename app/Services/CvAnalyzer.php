<?php

namespace App\Services;

/**
 * Port of analyze_cv_content() from ProjetATS scan.py.
 * Extended with configurable weights (F-16), semantic sub-score (F-12),
 * structured sections (F-13) and required experience gap (F-17).
 */
class CvAnalyzer
{
    /**
     * ProjetATS default weights (semantic defaults to 0 for score stability).
     *
     * @var array<string, float>
     */
    private const DEFAULT_WEIGHTS = [
        'skills' => 0.5,
        'experience' => 0.2,
        'education' => 0.1,
        'ats_quality' => 0.2,
        'semantic' => 0.0,
    ];

    public function __construct(
        private readonly SkillLibrary $skills,
        private readonly TfIdfSemanticMatcher $semanticMatcher,
        private readonly PersonalInfoExtractor $personalInfoExtractor,
        private readonly CvSectionDetector $sectionDetector = new CvSectionDetector,
        private readonly JobOfferRequirements $jobRequirements = new JobOfferRequirements,
    ) {}

    /**
     * @param  list<string>  $jobSkills
     * @return array{
     *     global_score: int,
     *     scores: array{skills: int, experience: int, education: int, ats_quality: int, semantic: int},
     *     personal_info: array<string, mixed>,
     *     cv_skills_detected: list<string>,
     *     matched_skills: list<string>,
     *     matched_types: array<string, string>,
     *     semantic_matches: list<array{skill: string, similarity: float, match_type: string}>,
     *     missing_skills: list<string>,
     *     ats_checks: array{text_extractable: bool, has_email: bool, has_phone: bool, has_sections: bool},
     *     sections: array<string, string>,
     *     section_keys: list<string>,
     *     experience_years: int,
     *     experience_required: int,
     *     experience_gap: int,
     *     education_found: list<string>,
     *     recommendations: list<string>,
     *     weights: array<string, float>,
     * }
     */
    public function analyze(string $cvText, array $jobSkills, string $jobOffer = ''): array
    {
        $cvLower = mb_strtolower($cvText);
        $cvSkillsFound = $this->skills->detectSkillsInCv($cvLower);

        $matched = [];
        $matchedTypes = [];

        foreach ($jobSkills as $skill) {
            [$isMatch, $matchType] = $this->skills->multiLevelMatch($skill, $cvLower, $cvSkillsFound);

            if ($isMatch) {
                $matched[] = $skill;
                $matchedTypes[$skill] = $matchType;
            }
        }

        $semanticResults = $this->semanticMatcher->match($jobSkills, $cvText, $matched);
        $semanticMatched = [];

        foreach ($semanticResults as $semanticResult) {
            if (! in_array($semanticResult['skill'], $matched, true)) {
                $matched[] = $semanticResult['skill'];
                $matchedTypes[$semanticResult['skill']] = 'semantic('.$semanticResult['similarity'].')';
                $semanticMatched[] = $semanticResult;
            }
        }

        $skillScore = $jobSkills !== [] ? (int) round((count($matched) / count($jobSkills)) * 100) : 0;
        $semanticScore = $this->semanticScore($semanticMatched, $jobSkills, $matched);

        $years = $this->experienceYears($cvLower);
        $expScore = match (true) {
            $years >= 5 => 100,
            $years >= 3 => 75,
            $years >= 1 => 50,
            default => 30,
        };

        $eduKeywords = ['master', 'bac+5', 'bac+4', 'licence', 'bachelor', 'diplome', 'degree', 'ingénieur', 'université', 'university'];
        $eduFound = [];

        foreach ($eduKeywords as $keyword) {
            if (str_contains($cvLower, $keyword)) {
                $eduFound[] = $keyword;
            }
        }

        $eduScore = $eduFound !== [] ? 80 : 0;

        $sections = $this->sectionDetector->detect($cvText);
        $sectionKeys = $this->sectionDetector->foundSections($cvText);

        $atsChecks = [
            'text_extractable' => trim($cvText) !== '',
            'has_email' => (bool) preg_match('/[\w\.-]+@[\w\.-]+\.\w+/', $cvText),
            'has_phone' => (bool) preg_match('/[\+]?[\d\s\-\(\)]{8,}/', $cvText),
            'has_sections' => $this->sectionDetector->hasStructuredSections($cvText)
                || (bool) preg_match('/(exp.rience|formation|comp.tence|skills|experience)/u', $cvLower),
        ];
        $atsScore = (int) round((count(array_filter($atsChecks)) / count($atsChecks)) * 100);

        $finalMissing = [];

        foreach ($jobSkills as $skill) {
            if (! in_array($skill, $matched, true)) {
                $finalMissing[] = $skill;
            }
        }

        $weights = $this->weights();
        $globalScore = (int) round(
            $skillScore * $weights['skills']
            + $expScore * $weights['experience']
            + $eduScore * $weights['education']
            + $atsScore * $weights['ats_quality']
            + $semanticScore * $weights['semantic']
        );
        $globalScore = max(0, min(100, $globalScore));

        $requirements = $this->jobRequirements->extract($jobOffer);
        $experienceRequired = $requirements['experience_required'];
        $experienceGap = max(0, $experienceRequired - $years);

        $recommendations = [];

        if (! $atsChecks['has_email']) {
            $recommendations[] = 'Ajoutez votre email';
        }

        if (! $atsChecks['has_phone']) {
            $recommendations[] = 'Ajoutez votre telephone';
        }

        if (! $atsChecks['has_sections']) {
            $recommendations[] = 'Structurez votre CV avec des sections';
        }

        if ($finalMissing !== []) {
            $recommendations[] = 'Competences manquantes : '.implode(', ', array_slice($finalMissing, 0, 5));
        }

        if ($experienceGap > 0) {
            $recommendations[] = sprintf(
                'Experience requise : %d an(s), vous en declarez %d — detaillez missions et durees reelles',
                $experienceRequired,
                $years
            );
        }

        return [
            'global_score' => $globalScore,
            'scores' => [
                'skills' => $skillScore,
                'experience' => $expScore,
                'education' => $eduScore,
                'ats_quality' => $atsScore,
                'semantic' => $semanticScore,
            ],
            'personal_info' => $this->personalInfoExtractor->extract($cvText),
            'cv_skills_detected' => $cvSkillsFound,
            'matched_skills' => $matched,
            'matched_types' => $matchedTypes,
            'semantic_matches' => $semanticMatched,
            'missing_skills' => $finalMissing,
            'ats_checks' => $atsChecks,
            'sections' => $sections,
            'section_keys' => $sectionKeys,
            'experience_years' => $years,
            'experience_required' => $experienceRequired,
            'experience_gap' => $experienceGap,
            'education_found' => $eduFound,
            'recommendations' => $recommendations,
            'weights' => $weights,
        ];
    }

    /**
     * @param  list<array{skill: string, similarity: float, match_type: string}>  $semanticMatched
     * @param  list<string>  $jobSkills
     * @param  list<string>  $matched
     */
    private function semanticScore(array $semanticMatched, array $jobSkills, array $matched): int
    {
        if ($semanticMatched !== []) {
            $sum = array_reduce(
                $semanticMatched,
                static fn (float $carry, array $m): float => $carry + (float) $m['similarity'],
                0.0
            );

            return max(0, min(100, (int) round($sum / count($semanticMatched) * 100)));
        }

        if ($jobSkills !== [] && count($matched) === count($jobSkills)) {
            return 100;
        }

        return 0;
    }

    /**
     * @return array<string, float>
     */
    private function weights(): array
    {
        try {
            if (function_exists('app') && app()->bound('config')) {
                /** @var mixed $configured */
                $configured = config('scan.weights');

                if (is_array($configured)) {
                    $merged = self::DEFAULT_WEIGHTS;

                    foreach ($configured as $key => $value) {
                        if (is_string($key) && is_numeric($value) && array_key_exists($key, self::DEFAULT_WEIGHTS)) {
                            $merged[$key] = (float) $value;
                        }
                    }

                    return $merged;
                }
            }
        } catch (Throwable) {
            // Unit tests without a booted container fall back to defaults.
        }

        return self::DEFAULT_WEIGHTS;
    }

    private function experienceYears(string $cvLower): int
    {
        $patterns = [
            '/(\d+)\s*(?:ans|years?|années?)/u',
            '/expérience\s*:\s*(\d+)/u',
            '/expérience\s+de\s+(\d+)/u',
        ];

        $years = 0;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cvLower, $match)) {
                $years = max($years, (int) $match[1]);
            }
        }

        return $years;
    }
}
