<?php

namespace App\Services;

/**
 * Detect named sections in a CV from header synonyms (FR / EN / MG).
 * Spec-inspired by ProjetATS plan §8 (section_detector — never ported).
 */
class CvSectionDetector
{
    /**
     * Section key => list of header title synonyms (lowercase, accent-insensitive match).
     *
     * @var array<string, list<string>>
     */
    private const HEADERS = [
        'personal' => [
            'informations personnelles', 'informations perso', 'contact', 'coordonnées',
            'personal information', 'personal details', 'contact information', 'profil de contact',
            'fahazanana', 'sandboxra', 'kontaky',
        ],
        'profile' => [
            'profil', 'résumé', 'resume', 'summary', 'about', 'à propos', 'presentation',
            'proposy', 'torolàlana',
        ],
        'experience' => [
            'expérience', 'experiences', 'expérience professionnelle', 'parcours professionnel',
            'work experience', 'professional experience', 'employment', 'emplois', 'career',
            'miasa', 'asa', 'taranjaba',
        ],
        'skills' => [
            'compétences', 'competences', 'compétences techniques', 'skills', 'technical skills',
            'technologies', 'outils', 'maîtrise', 'savoir-faire',
            'faha-maha-rindra', 'faha-tahiry',
        ],
        'education' => [
            'formation', 'formations', 'éducation', 'education', 'diplômes', 'diplomes',
            ' diplôme', 'etudes', 'études', 'academic', 'schooling',
            'fampianarana', 'tombontsoa',
        ],
        'certifications' => [
            'certifications', 'certificats', 'certificates', 'licenses', 'qualifications',
            'sasapanenjaka',
        ],
        'languages' => [
            'langues', 'languages', 'idiomes', 'fiteny',
        ],
        'interests' => [
            'centres d\'intérêt', 'centres d interet', 'hobbies', 'interests', 'loisirs',
            'hatsiaro', 'sanda',
        ],
    ];

    /**
     * @return array<string, string> section key => trimmed content (may be '')
     */
    public function detect(string $cvText): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $cvText) ?: [];
        $headerLineIndexes = [];
        $headerMap = [];

        foreach ($lines as $index => $line) {
            $key = $this->matchHeader(trim($line));

            if ($key !== null) {
                $headerLineIndexes[] = $index;
                $headerMap[$index] = $key;
            }
        }

        $sections = array_fill_keys(array_keys(self::HEADERS), '');

        if ($headerLineIndexes === []) {
            $sections['body'] = trim($cvText);

            return $sections;
        }

        $count = count($headerLineIndexes);

        foreach ($headerLineIndexes as $i => $lineIndex) {
            $key = $headerMap[$lineIndex];
            $end = $i + 1 < $count ? $headerLineIndexes[$i + 1] : count($lines);
            $bodyLines = array_slice($lines, $lineIndex + 1, $end - $lineIndex - 1);
            $headerRemainder = $this->headerRemainder($lines[$lineIndex]);

            if ($headerRemainder !== '') {
                array_unshift($bodyLines, $headerRemainder);
            }

            $body = implode("\n", $bodyLines);
            $sections[$key] = trim($sections[$key] === '' ? $body : $sections[$key]."\n".$body);
        }

        $before = implode("\n", array_slice($lines, 0, $headerLineIndexes[0]));

        if (trim($before) !== '') {
            $sections['personal'] = trim($sections['personal'] === '' ? $before : $before."\n".$sections['personal']);
        }

        return $sections;
    }

    /**
     * True when at least two known section headers are present (ATS-friendly structure).
     */
    public function hasStructuredSections(string $cvText): bool
    {
        $found = 0;
        $seen = [];

        foreach (preg_split('/\r\n|\r|\n/', $cvText) ?: [] as $line) {
            $key = $this->matchHeader(trim($line));

            if ($key !== null && ! isset($seen[$key])) {
                $seen[$key] = true;
                $found++;

                if ($found >= 2) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Keys of sections that have non-empty content.
     *
     * @return list<string>
     */
    public function foundSections(string $cvText): array
    {
        $found = [];

        foreach ($this->detect($cvText) as $key => $content) {
            if ($key !== 'body' && trim($content) !== '') {
                $found[] = $key;
            }
        }

        return $found;
    }

    private function matchHeader(string $line): ?string
    {
        if ($line === '' || mb_strlen($line) > 60) {
            return null;
        }

        $normalized = $this->normalize($line);

        foreach (self::HEADERS as $key => $titles) {
            foreach ($titles as $title) {
                $titleNorm = $this->normalize($title);

                if ($titleNorm !== '' && ($normalized === $titleNorm || str_starts_with($normalized, $titleNorm))) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Text after a matched header title on the same line (e.g. "Expérience: 5 ans").
     */
    private function headerRemainder(string $line): string
    {
        $normalizedLine = $this->normalize($line);

        foreach (self::HEADERS as $titles) {
            foreach ($titles as $title) {
                $titleNorm = $this->normalize($title);

                if ($titleNorm !== '' && str_starts_with($normalizedLine, $titleNorm)) {
                    $remainder = trim(substr($normalizedLine, mb_strlen($titleNorm)));

                    return $remainder;
                }
            }
        }

        return '';
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['’', '`', "'", '"'], '', $value);
        $value = (string) preg_replace('/[^\p{L}\p{N}+\/ ]+/u', ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
