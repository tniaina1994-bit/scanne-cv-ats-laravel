<?php

namespace App\Services;

/**
 * Deterministic FR phrasing suggestions for missing skills / ATS recommendations.
 * No external LLM — templates only.
 */
class ReformulationSuggester
{
    /**
     * @param  array<string, mixed>  $analysis
     * @return list<string>
     */
    public function suggest(array $analysis): array
    {
        $suggestions = [];

        foreach (array_slice((array) ($analysis['missing_skills'] ?? []), 0, 5) as $skill) {
            $skill = (string) $skill;
            $label = mb_strtolower($skill);

            $suggestions[] = sprintf(
                'Ajoutez une expérience ou un projet mettant en avant %s (outils, contexte, résultats chiffrés).',
                $skill
            );
            $suggestions[] = sprintf(
                'Intégrez « %s » dans votre résumé professionnel, par exemple : « Utilisation de %s pour… ».',
                $skill,
                $label
            );
        }

        $ats = (array) ($analysis['ats_checks'] ?? []);

        if (empty($ats['has_email'])) {
            $suggestions[] = 'Mettez votre email professionnel en en-tête du CV (format prenom.nom@domaine.com).';
        }

        if (empty($ats['has_phone'])) {
            $suggestions[] = 'Ajoutez un numéro de téléphone visible avec l’indicatif pays si besoin.';
        }

        if (empty($ats['has_sections'])) {
            $suggestions[] = 'Structurez le CV en sections claires : Profil, Expérience, Compétences, Formation.';
        }

        $recommendations = (array) ($analysis['recommendations'] ?? []);

        foreach (array_slice($recommendations, 0, 3) as $recommendation) {
            $suggestions[] = 'Reformulation suggérée : « '.str_replace('Ajoutez', 'Je mets en avant', (string) $recommendation).' » dans la rubrique adéquate.';
        }

        $years = (int) ($analysis['experience_years'] ?? 0);
        $required = (int) ($analysis['experience_required'] ?? 0);
        $gap = (int) ($analysis['experience_gap'] ?? 0);

        if ($required > 0 && $gap > 0) {
            $suggestions[] = sprintf(
                'Réécrivez votre expérience : « %d an(s) d\'expérience en … » + 2–3 missions datées qui totalisent environ %d an(s) pertinent%s pour ce poste.',
                max($years, 1),
                $required,
                $required > 1 ? 's' : ''
            );
            $suggestions[] = 'Si un stage / alternance compte, précisez-le (« dont 12 mois en alternance ») plutôt que de surestimer la durée.';
        } elseif ($required > 0 && $years >= $required) {
            $suggestions[] = sprintf(
                'Vous couvrez le minimum (%d an(s)) : placez cette durée dans le résumé professionnel en tête de CV.',
                $required
            );
        }

        $sections = array_values(array_filter((array) ($analysis['section_keys'] ?? [])));

        if ($sections !== [] && count($sections) < 3) {
            $suggestions[] = 'Ajoutez les sections manquantes parmi : Profil, Expérience, Compétences, Formation (titres courts, sans icônes exotiques).';
        }

        $educationFound = array_values(array_filter((array) ($analysis['education_found'] ?? [])));

        if ($educationFound === [] && (int) ($analysis['scores']['education'] ?? 0) === 0) {
            $suggestions[] = 'Formation : reformulez avec le diplôme exact + école + année (ex. « Licence Informatique — Univ. — 2022 »).';
        } elseif ($educationFound !== []) {
            $suggestions[] = 'Formation : alignez l\'intitulé sur l\'offre (mots-clés du diplôme) et ajoutez la spécialité si elle manque.';
        }

        $atsQuality = (int) ($analysis['scores']['ats_quality'] ?? 0);

        if ($atsQuality > 0 && $atsQuality < 80) {
            $suggestions[] = 'Qualité ATS basse : phrases courtes, puces « • », pas de tableau/colonne unique, en-tête texte (pas image).';
        }

        return array_values(array_unique($suggestions));
    }
}
