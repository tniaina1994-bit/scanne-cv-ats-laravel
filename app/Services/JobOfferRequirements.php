<?php

namespace App\Services;

/**
 * Parse required experience years from a job offer (F-17).
 */
class JobOfferRequirements
{
    /**
     * @return array{experience_required: int, languages: list<string>}
     */
    public function extract(string $jobOffer): array
    {
        $lower = mb_strtolower($jobOffer);
        $years = 0;

        $patterns = [
            '/(\d+)\s*\+?\s*(?:ans?|years?)\s*d\s*[\'’]exp[ée]rience/u',
            '/exp[ée]rience\s*:\s*(\d+)/u',
            '/exp[ée]rience\s+de\s+(\d+)/u',
            '/au\s+moins\s+(\d+)\s*ans?/u',
            '/minimum\s+(?:de\s+)?(\d+)\s*ans?/u',
            '/(\d+)\s*ans?\s+d\'exp[ée]rience/u',
            '/at\s+least\s+(\d+)\s*years?/u',
            '/(\d+)\+?\s*years?\s+(?:of\s+)?experience/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $lower, $match)) {
                $years = max($years, (int) $match[1]);
            }
        }

        $languages = [];

        foreach (['français', 'french', 'anglais', 'english', 'malagasy', 'malgache', 'espagnol', 'spanish', 'allemand', 'german'] as $lang) {
            if (str_contains($lower, $lang)) {
                $languages[] = $lang;
            }
        }

        return [
            'experience_required' => $years,
            'languages' => array_values(array_unique($languages)),
        ];
    }
}
