<?php

return [
    'title' => 'Scanne-CV-ATS',
    'tagline' => 'Analyze a CV against a job offer: compatibility score, matched or missing skills, ATS quality checks and recommendations.',
    'scan_cta' => 'Scan a CV',
    'scan_card' => 'Analyze a CV',
    'scan_card_desc' => 'Upload a PDF or DOCX, paste the job offer (templates included) and get the ATS score with skills and checks.',
    'open_scanner' => 'Open the scanner →',
    'extract_card' => 'Test text extraction',
    'extract_card_desc' => 'Test extraction alone: text PDF (smalot), DOCX (unzip + XML) or scanned PDF via OCR Tesseract fra+eng.',
    'open_extract' => 'Open extraction →',
    'history' => 'Scan history',
    'compare' => 'Compare 2 CVs',
    'how_title' => 'How it works',
    'how_1' => 'Drop the file (.pdf / .docx, max 10 MB).',
    'how_2' => 'Paste the job (templates: Support IT, Dev, DevOps, Data).',
    'how_3' => 'Exact → synonym → related → partial → semantic (TF-IDF).',
    'how_4' => 'Overall score, ATS checks, personal info, improvement tips.',
    'feat_ocr' => 'OCR fallback for scanned PDFs',
    'feat_score' => 'Weighted score',
    'feat_score_desc' => 'Skills 50% · XP 20% · Education 10% · ATS 20%',
    'footer' => 'Laravel port of ProjetATS',
];
