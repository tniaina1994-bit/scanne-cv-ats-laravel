<?php

namespace App\Services;

/**
 * Port of analyze_cv_content() from ProjetATS scan.py.
 */
class CvAnalyzer
{
    public function __construct(
        private readonly SkillLibrary $skills,
        private readonly TfIdfSemanticMatcher $semanticMatcher,
        private readonly PersonalInfoExtractor $personalInfoExtractor,
    ) {}

    /**
     * @param  list<string>  $jobSkills
     * @return array{
     *     global_score: int,
     *     scores: array{skills: int, experience: int, education: int, ats_quality: int},
     *     personal_info: array<string, mixed>,
     *     cv_skills_detected: list<string>,
     *     matched_skills: list<string>,
     *     matched_types: array<string, string>,
     *     semantic_matches: list<array{skill: string, similarity: float, match_type: string}>,
     *     missing_skills: list<string>,
     *     ats_checks: array{text_extractable: bool, has_email: bool, has_phone: bool, has_sections: bool},
     *     experience_years: int,
     *     education_found: list<string>,
     *     recommendations: list<string>,
     * }
     */
    public function analyze(string $cvText, array $jobSkills): array
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

        $atsChecks = [
            'text_extractable' => trim($cvText) !== '',
            'has_email' => (bool) preg_match('/[\w\.-]+@[\w\.-]+\.\w+/', $cvText),
            'has_phone' => (bool) preg_match('/[\+]?[\d\s\-\(\)]{8,}/', $cvText),
            'has_sections' => (bool) preg_match('/(exp.rience|formation|comp.tence|skills|experience)/u', $cvLower),
        ];
        $atsScore = (int) round((count(array_filter($atsChecks)) / count($atsChecks)) * 100);

        $finalMissing = [];

        foreach ($jobSkills as $skill) {
            if (! in_array($skill, $matched, true)) {
                $finalMissing[] = $skill;
            }
        }

        $globalScore = (int) round($skillScore * 0.5 + $expScore * 0.2 + $eduScore * 0.1 + $atsScore * 0.2);

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

        return [
            'global_score' => $globalScore,
            'scores' => [
                'skills' => $skillScore,
                'experience' => $expScore,
                'education' => $eduScore,
                'ats_quality' => $atsScore,
            ],
            'personal_info' => $this->personalInfoExtractor->extract($cvText),
            'cv_skills_detected' => $cvSkillsFound,
            'matched_skills' => $matched,
            'matched_types' => $matchedTypes,
            'semantic_matches' => $semanticMatched,
            'missing_skills' => $finalMissing,
            'ats_checks' => $atsChecks,
            'experience_years' => $years,
            'education_found' => $eduFound,
            'recommendations' => $recommendations,
        ];
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
