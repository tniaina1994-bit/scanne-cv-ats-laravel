<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Scan::query()->latest();

        if ($request->user()) {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                    ->orWhereNull('user_id');
            });
        }

        return view('history.index', [
            'scans' => $query->paginate(15),
        ]);
    }

    public function show(Request $request, Scan $scan): View
    {
        $this->authorizeScan($request, $scan);

        return view('history.show', [
            'scan' => $scan,
            'shareUrl' => URL::signedRoute('history.share', $scan),
        ]);
    }

    public function share(Request $request, Scan $scan): View
    {
        return view('history.show', [
            'scan' => $scan,
            'shareUrl' => URL::signedRoute('history.share', $scan),
            'isShared' => true,
        ]);
    }

    public function exportCsv(Request $request, Scan $scan): StreamedResponse
    {
        $this->authorizeScan($request, $scan);

        $filename = 'scan-'.$scan->id.'-rapport.csv';
        $result = $scan->result;
        $analysis = $result['analysis'] ?? [];

        return response()->streamDownload(function () use ($scan, $result, $analysis) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Champ', 'Valeur']);
            fputcsv($handle, ['Fichier', $scan->filename]);
            fputcsv($handle, ['Score global', $scan->score]);
            fputcsv($handle, ['OCR utilisé', $scan->ocr_used ? 'oui' : 'non']);
            fputcsv($handle, ['Date', $scan->created_at?->toDateTimeString() ?? '']);
            fputcsv($handle, ['Temps traitement (ms)', (string) ($result['processing_time_ms'] ?? '')]);

            foreach (['skills', 'experience', 'education', 'ats_quality', 'semantic'] as $key) {
                fputcsv($handle, ['Sous-score '.$key, (string) ($analysis['scores'][$key] ?? '')]);
            }

            fputcsv($handle, ['Sections détectées', implode(' | ', $analysis['section_keys'] ?? [])]);
            fputcsv($handle, ['Expérience requise (ans)', (string) ($analysis['experience_required'] ?? '')]);
            fputcsv($handle, ['Écart expérience (ans)', (string) ($analysis['experience_gap'] ?? '')]);

            fputcsv($handle, ['Compétences matchées', implode(' | ', $analysis['matched_skills'] ?? [])]);
            fputcsv($handle, ['Compétences manquantes', implode(' | ', $analysis['missing_skills'] ?? [])]);
            fputcsv($handle, ['Recommandations', implode(' | ', $analysis['recommendations'] ?? [])]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Request $request, Scan $scan): RedirectResponse
    {
        $this->authorizeScan($request, $scan);
        $scan->delete();

        return redirect()->route('history.index')->with('status', __('history.deleted'));
    }

    private function authorizeScan(Request $request, Scan $scan): void
    {
        if ($scan->user_id === null || $request->user()?->id === $scan->user_id) {
            return;
        }

        abort(403);
    }
}
