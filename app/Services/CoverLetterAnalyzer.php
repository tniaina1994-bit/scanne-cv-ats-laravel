<?php

namespace App\Services;

/**
 * Cover letter vs job offer analysis (F-10).
 * Reuses SkillLibrary + TF-IDF matcher; deterministic FR suggestions (no LLM).
 */
class CoverLetterAnalyzer
{
    public function __construct(
        private readonly SkillLibrary $skills,
        private readonly TfIdfSemanticMatcher $semanticMatcher,
    ) {}

    /**
     * @param  list<string>  $jobSkills
     * @return array{
     *     global_score: int,
     *     scores: array{skills: int, length: int, structure: int, alignment: int},
     *     matched_skills: list<string>,
     *     missing_skills: list<string>,
     *     semantic_matches: list<array{skill: string, similarity: float, match_type: string}>,
     *     checks: array{has_greeting: bool, has_closing: bool, has_length: bool, has_offer_keywords: bool},
     *     word_count: int,
     *     suggestions: list<string>,
     * }
     */
    public function analyze(string $letter, string $jobOffer, array $jobSkills): array
    {
        $letterLower = mb_strtolower($letter);
        $wordCount = count(preg_split('/\s+/u', trim($letter)) ?: []);
        $matched = [];
        $matchedTypes = [];

        foreach ($jobSkills as $skill) {
            [$isMatch] = $this->skills->multiLevelMatch($skill, $letterLower, []);

            if ($isMatch) {
                $matched[] = $skill;
                $matchedTypes[$skill] = 'match';
            }
        }

        $semanticResults = $this->semanticMatcher->match($jobSkills, $letter, $matched);
        $semanticMatched = [];

        foreach ($semanticResults as $result) {
            if (! in_array($result['skill'], $matched, true)) {
                $matched[] = $result['skill'];
                $semanticMatched[] = $result;
            }
        }

        $missing = array_values(array_diff($jobSkills, $matched));
        $skillScore = $jobSkills !== [] ? (int) round((count($matched) / count($jobSkills)) * 100) : (trim($letter) !== '' ? 50 : 0);

        $lengthScore = match (true) {
            $wordCount >= 120 && $wordCount <= 600 => 100,
            $wordCount >= 60 => 70,
            $wordCount >= 30 => 40,
            default => 0,
        };

        $hasGreeting = (bool) preg_match(
            '/(madame|monsieur|dear|honor[eé]s|à l.attention|to whom|chère|cher )/iu',
            $letter
        );
        $hasClosing = (bool) preg_match('/(cordialement|sincèrement|respectueusement|salutations|kind regards|best regards|je vous prie|looking forward)/iu', $letter);
        $hasOfferKeywords = $this->hasOfferKeywords($letter, $jobOffer);

        $structureScore = (int) round(
            (($hasGreeting ? 1 : 0) + ($hasClosing ? 1 : 0) + ($hasOfferKeywords ? 1 : 0) + ($lengthScore >= 70 ? 1 : 0)) / 4 * 100
        );

        $alignmentScore = $skillScore;
        $globalScore = (int) round($skillScore * 0.45 + $lengthScore * 0.15 + $structureScore * 0.2 + $alignmentScore * 0.2);

        $suggestions = [];

        if (! $hasGreeting) {
            $suggestions[] = 'Ajoutez une formule d\'accueil (Madame, Monsieur / Dear…).';
        }

        if (! $hasClosing) {
            $suggestions[] = 'Terminez par une formule de politesse (Cordialement / Kind regards).';
        }

        if ($lengthScore < 70) {
            $suggestions[] = 'Visez 150–400 mots : contexte, valeur ajoutée, appel à l\'action.';
        }

        if (! $hasOfferKeywords) {
            $suggestions[] = 'Reprenez 3–5 mots-clés exacts de l\'offre dans votre lettre.';
        }

        foreach (array_slice($missing, 0, 4) as $skill) {
            $suggestions[] = sprintf(
                'Mentionnez explicitement « %s » avec un résultat concret lié au poste.',
                $skill
            );
        }

        if ($jobSkills !== [] && $skillScore >= 70 && $structureScore >= 75) {
            $suggestions[] = 'Lettre bien alignée : personnalisez l\'accroche (entreprise, mission récente).';
        }

        return [
            'global_score' => max(0, min(100, $globalScore)),
            'scores' => [
                'skills' => $skillScore,
                'length' => $lengthScore,
                'structure' => $structureScore,
                'alignment' => $alignmentScore,
            ],
            'matched_skills' => $matched,
            'missing_skills' => $missing,
            'semantic_matches' => $semanticMatched,
            'checks' => [
                'has_greeting' => $hasGreeting,
                'has_closing' => $hasClosing,
                'has_length' => $lengthScore >= 70,
                'has_offer_keywords' => $hasOfferKeywords,
            ],
            'word_count' => $wordCount,
            'suggestions' => array_values(array_unique($suggestions)),
        ];
    }

    private function hasOfferKeywords(string $letter, string $jobOffer): bool
    {
        $letterLower = mb_strtolower($letter);
        $words = preg_split('/\W+/u', mb_strtolower($jobOffer)) ?: [];
        $stop = ['the', 'and', 'for', 'you', 'your', 'with', 'are', 'our', 'les', 'des', 'une', 'pour', 'dans', 'que', 'qui', 'sur'];
        $hits = 0;
        $checked = 0;

        foreach ($words as $word) {
            if (mb_strlen($word) < 5 || in_array($word, $stop, true)) {
                continue;
            }

            $checked++;

            if (str_contains($letterLower, $word)) {
                $hits++;
            }

            if ($checked >= 25) {
                break;
            }
        }

        return $checked > 0 && $hits >= 3;
    }
}
