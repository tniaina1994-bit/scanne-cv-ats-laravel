<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OCR processing mode
    |--------------------------------------------------------------------------
    |
    | false (default): PDFs without a native text layer are OCR'd synchronously
    | during the request, then analyzed like any other CV (matches ProjetATS).
    |
    | true: OCR is dispatched to the queue (requires `php artisan queue:work`).
    |
    */

    'ocr_async' => (bool) env('SCAN_OCR_ASYNC', false),

    /*
    |--------------------------------------------------------------------------
    | Score weights (F-16)
    |--------------------------------------------------------------------------
    |
    | Weights used for global_score. Defaults match ProjetATS:
    | skills*0.5 + experience*0.2 + education*0.1 + ats_quality*0.2.
    | semantic defaults to 0 so historical scores stay identical; set
    | ATS_WEIGHT_SEMANTIC=0.15 to blend TF-IDF similarity into the total.
    |
    */

    'weights' => [
        'skills' => (float) env('ATS_WEIGHT_SKILLS', 0.5),
        'experience' => (float) env('ATS_WEIGHT_EXPERIENCE', 0.2),
        'education' => (float) env('ATS_WEIGHT_EDUCATION', 0.1),
        'ats_quality' => (float) env('ATS_WEIGHT_ATS', 0.2),
        'semantic' => (float) env('ATS_WEIGHT_SEMANTIC', 0.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Semantic (TF-IDF) threshold
    |--------------------------------------------------------------------------
    */

    'semantic_threshold' => (float) env('ATS_SEMANTIC_THRESHOLD', 0.15),

    /*
    |--------------------------------------------------------------------------
    | Matching languages (F-15)
    |--------------------------------------------------------------------------
    |
    | Codes used for multi-language skill aliases (fr / en / mg).
    |
    */

    'languages' => ['fr', 'en', 'mg'],

];
