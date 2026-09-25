<?php

namespace Database\Factories;

use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scan>
 */
class ScanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = fake()->numberBetween(0, 100);

        return [
            'user_id' => null,
            'filename' => fake()->word().'.pdf',
            'score' => $score,
            'ocr_used' => false,
            'job_offer' => "Offre test\nDocker Linux",
            'job_offer_html' => '<p>Offre test</p>',
            'result' => [
                'filename' => 'cv.pdf',
                'ocr_used' => false,
                'cv_text_preview' => 'Preview',
                'job_skills_found' => ['Docker', 'Linux'],
                'analysis' => [
                    'global_score' => $score,
                    'scores' => [
                        'skills' => $score,
                        'experience' => 50,
                        'education' => 40,
                        'ats_quality' => 60,
                    ],
                    'personal_info' => [],
                    'cv_skills_detected' => ['Docker'],
                    'matched_skills' => ['Docker'],
                    'matched_types' => ['Docker' => 'exact'],
                    'semantic_matches' => [],
                    'missing_skills' => ['Linux'],
                    'ats_checks' => [
                        'text_extractable' => true,
                        'has_email' => true,
                        'has_phone' => true,
                        'has_sections' => true,
                    ],
                    'experience_years' => 3,
                    'education_found' => ['licence'],
                    'recommendations' => ['Ajouter Linux au CV'],
                ],
                'processing_time_ms' => 12,
            ],
            'content_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
