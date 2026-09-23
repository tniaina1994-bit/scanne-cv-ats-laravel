<?php

namespace App\Services;

class PersonalInfoExtractor
{
    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     phone: string,
     *     linkedin: string,
     *     github: string,
     *     website: string,
     *     location: string,
     *     languages: list<string>,
     *     has_driving_license: bool,
     *     nationality: string,
     * }
     */
    public function extract(string $cvText): array
    {
        $cvLower = mb_strtolower($cvText);

        $email = '';
        if (preg_match('/[\w\.\-+]+@[\w\.\-]+\.\w+/', $cvText, $emailMatch)) {
            $email = $emailMatch[0];
        }

        $phone = '';
        if (preg_match('/[\+]?[\d\s\-\(\)]{8,}/', $cvText, $phoneMatch)) {
            $phone = trim($phoneMatch[0]);
        }

        $linkedin = '';
        if (preg_match('/linkedin\.com\/in\/[\w\-\%\._]+/i', $cvText, $linkedinMatch)) {
            $linkedin = $linkedinMatch[0];
        }

        $github = '';
        if (preg_match('/github\.com\/[\w\-]+/i', $cvText, $githubMatch)) {
            $github = $githubMatch[0];
        }

        $website = '';
        $websitePattern = '~(?<![\w@.])(?:https?://)?(?:[\w\-]+\.)+(?:com|fr|io|dev|net|org|edu|gov|me|app|tech|cloud|ai|co|info|biz|xyz|site|online|store|name|pro)(?:/[\w\-\./]*)?~i';
        if (preg_match_all($websitePattern, $cvText, $websiteMatches)) {
            foreach ($websiteMatches[0] as $candidate) {
                $host = mb_strtolower(parse_url(str_contains($candidate, '://') ? $candidate : 'https://'.$candidate, PHP_URL_HOST) ?? '');

                if (str_contains($host, 'linkedin') || str_contains($host, 'github')) {
                    continue;
                }

                $website = $candidate;
                break;
            }
        }

        $location = '';
        $cityPatterns = [
            '/(?:located|situé|située|basé|basée|ville|city)\s*:\s*([A-ZÀ-Ÿa-zÀ-ÿ\s\-]+)/u',
            '/([A-ZÀ-Ÿ][a-zÀ-ÿ]+(?:-[A-ZÀ-Ÿ][a-zÀ-ÿ]+)?)\s*,?\s*(?:France|Madagascar|Tunisie|Maroc|Cameroun|Sénégal|Belgique|Canada|Allemagne)/u',
        ];

        foreach ($cityPatterns as $pattern) {
            if (preg_match($pattern, $cvText, $locationMatch)) {
                $location = trim($locationMatch[0]);
                break;
            }
        }

        $langKeywords = [
            'français', 'french', 'english', 'anglais', 'malagasy', 'malgache',
            'espagnol', 'spanish', 'allemand', 'german', 'arabe', 'arabic',
            'chinois', 'chinese', 'japonais', 'japanese', 'portugais', 'portuguese',
        ];

        $languages = [];

        foreach ($langKeywords as $keyword) {
            if (str_contains($cvLower, $keyword)) {
                $languages[] = mb_convert_case($keyword, MB_CASE_TITLE);
            }
        }

        $hasDrivingLicense = (bool) preg_match('/(permis\s*[ab]|driving\s*license|licence\s+de\s+conduire)/u', $cvLower);

        $nationality = '';
        if (preg_match('/(?:nationalité|nationality)\s*:\s*([A-ZÀ-Ÿa-zÀ-ÿ\s]+)/iu', $cvText, $nationalityMatch)) {
            $nationality = trim($nationalityMatch[1]);
        }

        $name = '';

        foreach (preg_split('/\r\n|\r|\n/', $cvText) ?: [] as $line) {
            $line = trim($line);

            if (mb_strlen($line) <= 2 || mb_strlen($line) >= 50) {
                continue;
            }

            if (str_contains($line, '@') || preg_match('/\d/', $line)) {
                continue;
            }

            if (preg_match('/(cv|resume|curriculum|expérience|compétence|formation|skills|email|téléphone|phone|adresse)/iu', $line)) {
                continue;
            }

            $name = $line;
            break;
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'linkedin' => $linkedin,
            'github' => $github,
            'website' => $website,
            'location' => $location,
            'languages' => $languages,
            'has_driving_license' => $hasDrivingLicense,
            'nationality' => $nationality,
        ];
    }
}
