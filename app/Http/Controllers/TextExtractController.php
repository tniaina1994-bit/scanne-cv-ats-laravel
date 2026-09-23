<?php

namespace App\Http\Controllers;

use App\Services\DocumentTextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class TextExtractController extends Controller
{
    private const SAMPLE_DIR = '/home/mandry/Documents/ProjetWeb/ProjetATS/ats-cv-analyzer/data';

    public function __construct(private readonly DocumentTextExtractor $extractor) {}

    public function index(): View
    {
        return $this->viewIndex(
            result: session('extract.result'),
            error: session('extract.error'),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
        ]);

        $file = $request->file('file');

        return $this->extractAndRedirect(
            filename: $file->getClientOriginalName(),
            path: (string) $file->getRealPath(),
            extension: strtolower($file->getClientOriginalExtension()),
            size: (int) $file->getSize(),
        );
    }

    public function sample(Request $request): RedirectResponse
    {
        $request->validate([
            'sample' => ['required', 'string'],
        ]);

        $path = $this->resolveSample($request->input('sample'));

        if ($path === null) {
            return redirect()
                ->route('extract.index')
                ->with('extract.error', 'Exemple inconnu.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $this->extractAndRedirect(
            filename: basename($path),
            path: $path,
            extension: $extension,
            size: (int) filesize($path),
        );
    }

    private function extractAndRedirect(string $filename, string $path, string $extension, int $size): RedirectResponse
    {
        $startedAt = microtime(true);

        try {
            $result = $this->extractor->extract($path, $extension);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('extract.index')
                ->with('extract.error', $e->getMessage());
        }

        return redirect()
            ->route('extract.index')
            ->with('extract.result', [
                'filename' => $filename,
                'extension' => $extension,
                'size' => $size,
                'method' => $result['method'],
                'ocrUsed' => $result['ocr_used'],
                'chars' => mb_strlen($result['text']),
                'ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'text' => $result['text'],
            ]);
    }

    private function viewIndex(?array $result = null, ?string $error = null): View
    {
        return view('extract.index', [
            'samples' => $this->samples(),
            'result' => $result,
            'error' => $error,
        ]);
    }

    private function resolveSample(string $name): ?string
    {
        foreach ($this->samples() as $sample) {
            if ($sample['name'] === $name) {
                return $sample['file'];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{name: string, file: string, label: string}>
     */
    private function samples(): array
    {
        $files = [
            'CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.pdf',
            'CV_RAKOTONJARY_MANDRINIRINA_FRANCOIS.docx',
            'cv_support_it_perfect.docx',
        ];

        $samples = [];

        foreach ($files as $file) {
            $path = self::SAMPLE_DIR.'/'.$file;

            if (is_file($path)) {
                $samples[] = [
                    'name' => $file,
                    'file' => $path,
                    'label' => $file.' ('.number_format((int) filesize($path) / 1024, 0).' KB)',
                ];
            }
        }

        return $samples;
    }
}
