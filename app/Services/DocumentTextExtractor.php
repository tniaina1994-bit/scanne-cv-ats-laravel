<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class DocumentTextExtractor
{
    private const DOCX_XML = 'word/document.xml';

    /**
     * @return array{text: string, method: string, ocr_used: bool}
     */
    public function extract(string $path, string $extension, bool $allowOcr = true): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Fichier introuvable.');
        }

        return match (strtolower($extension)) {
            'pdf' => $this->extractPdf($path, $allowOcr),
            'docx' => $this->extractDocx($path),
            default => throw new RuntimeException('Format non pris en charge : '.$extension),
        };
    }

    /**
     * @return array{text: string, method: string, ocr_used: bool}
     */
    private function extractPdf(string $path, bool $allowOcr = true): array
    {
        $text = '';

        try {
            $parser = new Parser;
            $text = $parser->parseFile($path)->getText();
        } catch (Throwable) {
            $text = '';
        }

        if (trim($text) !== '') {
            return [
                'text' => trim($text),
                'method' => 'smalot/pdfparser',
                'ocr_used' => false,
            ];
        }

        if (! $allowOcr) {
            return [
                'text' => '',
                'method' => 'none',
                'ocr_used' => false,
            ];
        }

        return [
            'text' => trim($this->ocrPdf($path)),
            'method' => 'pdftoppm+tesseract',
            'ocr_used' => true,
        ];
    }

    private function ocrPdf(string $path): string
    {
        $dir = storage_path('app/private/ocr-'.uniqid('', true));

        if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Impossible de créer un dossier temporaire pour l’OCR.');
        }

        try {
            $prefix = $dir.'/page';
            $render = Process::timeout(120)->run(['pdftoppm', '-png', '-r', '200', $path, $prefix]);

            if (! $render->successful()) {
                throw new RuntimeException('Échec de conversion du PDF en images (pdftoppm).');
            }

            $images = glob($prefix.'-*.png') ?: [];

            if ($images === []) {
                throw new RuntimeException('Aucune page image générée pour l’OCR.');
            }

            sort($images, SORT_NATURAL);

            $text = '';

            foreach ($images as $image) {
                $ocr = Process::timeout(60)
                    ->env(['OMP_THREAD_LIMIT' => '1', 'OMP_NUM_THREADS' => '1'])
                    ->run(['tesseract', $image, 'stdout', '-l', 'fra+eng', '--psm', '6']);

                if ($ocr->successful()) {
                    $text .= $ocr->output()."\n";
                }
            }

            return $text;
        } finally {
            foreach (glob($dir.'/*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($dir);
        }
    }

    /**
     * @return array{text: string, method: string, ocr_used: bool}
     */
    private function extractDocx(string $path): array
    {
        $result = Process::timeout(60)->run(['unzip', '-p', $path, self::DOCX_XML]);

        if (! $result->successful() || trim($result->output()) === '') {
            throw new RuntimeException('Impossible de lire le DOCX (word/document.xml manquant ou archive corrompue).');
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $loaded = $dom->loadXML($result->output());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('DOCX invalide : XML illisible.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $lines = [];

        foreach ($xpath->query('//w:p') as $paragraph) {
            $parts = [];

            foreach ($xpath->query('.//w:t', $paragraph) as $node) {
                $parts[] = $node->textContent;
            }

            $line = trim(implode('', $parts));

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        $text = implode("\n", $lines);

        if (trim($text) === '') {
            throw new RuntimeException('Aucun texte extrait du DOCX.');
        }

        return [
            'text' => $text,
            'method' => 'unzip+xml',
            'ocr_used' => false,
        ];
    }
}
