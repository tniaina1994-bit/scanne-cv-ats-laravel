<?php

namespace App\Services;

/**
 * Pure-PHP port of the sklearn TF-IDF + cosine semantic matching from ProjetATS scan.py.
 */
class TfIdfSemanticMatcher
{
    private const SIMILARITY_THRESHOLD = 0.15;

    private const MAX_FEATURES = 5000;

    /**
     * sklearn's ENGLISH_STOP_WORDS.
     *
     * @var list<string>
     */
    private const STOP_WORDS = [
        'a', 'about', 'above', 'across', 'after', 'afterwards', 'again', 'against', 'all', 'almost', 'alone', 'along',
        'already', 'also', 'although', 'always', 'am', 'among', 'amongst', 'amoungst', 'amount', 'an', 'and', 'another',
        'any', 'anyhow', 'anyone', 'anything', 'anyway', 'anywhere', 'are', 'around', 'as', 'at', 'back', 'be',
        'became', 'because', 'become', 'becomes', 'becoming', 'been', 'before', 'beforehand', 'behind', 'being',
        'below', 'beside', 'besides', 'between', 'beyond', 'bill', 'both', 'bottom', 'but', 'by', 'call', 'can',
        'cannot', 'cant', 'co', 'computer', 'con', 'could', 'couldnt', 'cry', 'de', 'describe', 'detail', 'do',
        'done', 'down', 'due', 'during', 'each', 'eg', 'eight', 'either', 'eleven', 'else', 'elsewhere', 'empty',
        'enough', 'etc', 'even', 'ever', 'every', 'everyone', 'everything', 'everywhere', 'except', 'few', 'fifteen',
        'fify', 'fill', 'find', 'fire', 'first', 'five', 'for', 'former', 'formerly', 'forty', 'found', 'four', 'from',
        'front', 'full', 'further', 'get', 'give', 'go', 'had', 'has', 'hasnt', 'have', 'he', 'hence', 'her',
        'here', 'hereafter', 'hereby', 'herein', 'hereupon', 'hers', 'herself', 'him', 'himself', 'his', 'how',
        'however', 'hundred', 'i', 'ie', 'if', 'in', 'inc', 'indeed', 'interest', 'into', 'is', 'it', 'its', 'itself',
        'keep', 'last', 'latter', 'latterly', 'least', 'less', 'ltd', 'made', 'many', 'may', 'me', 'meanwhile',
        'might', 'mill', 'mine', 'more', 'moreover', 'most', 'mostly', 'move', 'much', 'must', 'my', 'myself',
        'name', 'namely', 'neither', 'never', 'nevertheless', 'next', 'nine', 'no', 'nobody', 'none', 'noone',
        'nor', 'not', 'nothing', 'now', 'nowhere', 'of', 'off', 'often', 'on', 'once', 'one', 'only', 'or', 'other',
        'others', 'otherwise', 'ours', 'ourselves', 'out', 'over', 'own', 'per', 'perhaps', 'please', 'put', 'rather',
        're', 'same', 'see', 'seem', 'seemed', 'seeming', 'seems', 'serious', 'several', 'she', 'should', 'show',
        'side', 'since', 'sincere', 'six', 'sixty', 'so', 'some', 'somehow', 'someone', 'something', 'sometime',
        'sometimes', 'somewhere', 'still', 'such', 'system', 'take', 'ten', 'than', 'that', 'the', 'their', 'them',
        'themselves', 'then', 'thence', 'there', 'thereafter', 'thereby', 'therefore', 'therein', 'thereupon',
        'these', 'they', 'thick', 'thin', 'third', 'this', 'those', 'though', 'three', 'through', 'throughout',
        'thru', 'thus', 'to', 'together', 'too', 'top', 'toward', 'towards', 'twelve', 'twenty', 'two', 'un', 'under',
        'until', 'up', 'upon', 'us', 'very', 'via', 'was', 'we', 'well', 'were', 'what', 'whatever', 'when',
        'whence', 'whenever', 'where', 'whereafter', 'whereas', 'whereby', 'wherein', 'whereupon', 'wherever',
        'whether', 'which', 'while', 'whither', 'who', 'whoever', 'whole', 'whom', 'whose', 'why', 'will', 'with',
        'within', 'without', 'would', 'yet', 'you', 'your', 'yours', 'yourself', 'yourselves',
    ];

    /**
     * @param  list<string>  $jobSkills
     * @param  list<string>  $matchedExact
     * @return list<array{skill: string, similarity: float, match_type: string}>
     */
    public function match(array $jobSkills, string $cvText, array $matchedExact): array
    {
        $unmatched = [];

        foreach ($jobSkills as $skill) {
            if (! in_array($skill, $matchedExact, true)) {
                $unmatched[] = $skill;
            }
        }

        if ($unmatched === [] || trim($cvText) === '') {
            return [];
        }

        $cvChunks = preg_split('/\n\s*\n/', $cvText) ?: [];
        $cvChunks = array_values(array_filter(array_map(trim(...), $cvChunks), static fn (string $chunk): bool => $chunk !== ''));

        if ($cvChunks === []) {
            $cvChunks = [$cvText];
        }

        $allDocs = [...$cvChunks, $cvText];
        $semanticResults = [];

        foreach ($unmatched as $skill) {
            $skillText = str_replace(['_', '-'], ' ', mb_strtolower($skill));
            $docs = [$skillText, ...$allDocs];

            $vectors = $this->tfidfVectors($docs);

            if ($vectors === []) {
                continue;
            }

            $skillVector = $vectors[0];
            $maxSimilarity = 0.0;

            for ($i = 1, $count = count($vectors); $i < $count; $i++) {
                $similarity = $this->cosineSimilarity($skillVector, $vectors[$i]);

                if ($similarity > $maxSimilarity) {
                    $maxSimilarity = $similarity;
                }
            }

            if ($maxSimilarity > self::SIMILARITY_THRESHOLD) {
                $semanticResults[] = [
                    'skill' => $skill,
                    'similarity' => round($maxSimilarity, 3),
                    'match_type' => 'semantic',
                ];
            }
        }

        return $semanticResults;
    }

    /**
     * L2-normalized TF-IDF row vectors (sklearn defaults: raw tf, smooth idf, l2 norm).
     *
     * @param  list<string>  $docs
     * @return list<array<string, float>>
     */
    private function tfidfVectors(array $docs): array
    {
        $tokenized = [];

        foreach ($docs as $doc) {
            $tokens = $this->tokenize($doc);

            if (count($tokens) > self::MAX_FEATURES) {
                $tokens = array_slice($tokens, 0, self::MAX_FEATURES);
            }

            $tokenized[] = $tokens;
        }

        $docCount = count($tokenized);

        if ($docCount === 0) {
            return [];
        }

        /** @var array<string, int> $documentFrequency */
        $documentFrequency = [];

        foreach ($tokenized as $tokens) {
            $uniqueInDoc = array_unique($tokens);

            foreach ($uniqueInDoc as $term) {
                $documentFrequency[$term] = ($documentFrequency[$term] ?? 0) + 1;
            }
        }

        $idf = [];

        foreach ($documentFrequency as $term => $df) {
            $idf[$term] = log((1 + $docCount) / (1 + $df)) + 1.0;
        }

        $vectors = [];

        foreach ($tokenized as $tokens) {
            /** @var array<string, int> $termFrequency */
            $termFrequency = [];

            foreach ($tokens as $term) {
                $termFrequency[$term] = ($termFrequency[$term] ?? 0) + 1;
            }

            $weights = [];

            foreach ($termFrequency as $term => $tf) {
                $weights[$term] = $tf * ($idf[$term] ?? 0.0);
            }

            $norm = 0.0;

            foreach ($weights as $weight) {
                $norm += $weight * $weight;
            }

            $norm = sqrt($norm);

            if ($norm > 0) {
                foreach ($weights as $term => $weight) {
                    $weights[$term] = $weight / $norm;
                }
            }

            $vectors[] = $weights;
        }

        return $vectors;
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        preg_match_all('/\b\w\w+\b/u', mb_strtolower($text), $matches);

        return array_values(array_filter(
            $matches[0] ?? [],
            static fn (string $token): bool => ! in_array($token, self::STOP_WORDS, true)
        ));
    }

    /**
     * @param  array<string, float>  $a
     * @param  array<string, float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $term => $weight) {
            $normA += $weight * $weight;
        }

        foreach ($b as $term => $weight) {
            $normB += $weight * $weight;

            if (isset($a[$term])) {
                $dot += $a[$term] * $weight;
            }
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
