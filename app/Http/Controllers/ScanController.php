<?php

namespace App\Http\Controllers;

use App\Services\CvAnalyzer;
use App\Services\DocumentTextExtractor;
use App\Services\SkillLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ScanController extends Controller
{
    /**
     * Job offer templates ported from ProjetATS scan.component.ts (HTML for the rich editor).
     *
     * @var array<string, string>
     */
    private const TEMPLATES = [
        'support-it' => '<h2>Support Technique Informatique &amp; Administration Réseau Système</h2><h3>Compétences requises</h3><ul><li>Windows Server</li><li>Active Directory</li><li>Linux (Ubuntu, Debian)</li><li>TCP/IP</li><li>DNS</li><li>DHCP</li><li>VPN</li><li>Firewall</li><li>Virtualisation (VMware, Hyper-V)</li><li>Cisco</li><li>MikroTik</li><li>Migration de données</li></ul><h3>Missions</h3><ul><li>Installation et configuration de postes de travail</li><li>Gestion des comptes utilisateurs et des droits d\'accès</li><li>Maintenance des serveurs et infrastructures réseau</li><li>Dépannage et résolution des incidents techniques</li><li>Mise en place et administration des équipements réseau</li><li>Sauvegarde et restauration des données</li><li>Suivi des performances du réseau</li><li>Documentation technique et reporting</li></ul>',
        'dev' => '<h2>Développeur Full Stack</h2><h3>Compétences requises</h3><ul><li>Python</li><li>JavaScript</li><li>React</li><li>Node.js</li><li>PostgreSQL</li><li>Git</li></ul><h3>Missions</h3><ul><li>Développement d\'applications web</li><li>Maintenance du code existant</li><li>Participation aux revues de code</li></ul>',
        'devops' => '<h2>Ingénieur DevOps</h2><h3>Compétences requises</h3><ul><li>Docker</li><li>Kubernetes</li><li>CI/CD</li><li>AWS ou Azure</li><li>Linux</li><li>Terraform</li></ul><h3>Missions</h3><ul><li>Mise en place de pipelines CI/CD</li><li>Gestion de l\'infrastructure cloud</li><li>Automatisation du déploiement</li></ul>',
        'data' => '<h2>Data Engineer</h2><h3>Compétences requises</h3><ul><li>Python</li><li>SQL</li><li>Apache Spark</li><li>Airflow</li><li>AWS/GCP</li><li>ETL</li></ul><h3>Missions</h3><ul><li>Conception de pipelines de données</li><li>Optimisation des performances</li><li>Qualité des données</li></ul>',
    ];

    /**
     * @var array<string, string>
     */
    private const TEMPLATE_LABELS = [
        'support-it' => 'Support IT',
        'dev' => 'Développeur',
        'devops' => 'DevOps',
        'data' => 'Data',
    ];

    public function __construct(
        private readonly DocumentTextExtractor $extractor,
        private readonly CvAnalyzer $analyzer,
        private readonly SkillLibrary $skillLibrary,
    ) {}

    public function index(): View
    {
        return $this->viewResult(
            error: session('scan.error'),
            jobOffer: session('scan.job_offer'),
            jobOfferHtml: session('scan.job_offer_html'),
            result: session('scan.result'),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'job_offer' => ['required', 'string', 'max:50000'],
            'job_offer_html' => ['nullable', 'string', 'max:50000'],
        ], [], [
            'job_offer' => "l'offre d'emploi",
        ]);

        $file = $request->file('file');
        $jobOffer = (string) $request->input('job_offer');
        $jobOfferHtmlInput = (string) $request->input('job_offer_html', '');
        $jobOfferHtml = $this->sanitizeJobOfferHtml($jobOfferHtmlInput !== '' ? $jobOfferHtmlInput : $jobOffer);

        if (trim($jobOffer) === '') {
            $jobOffer = trim(html_entity_decode(strip_tags($jobOfferHtml), ENT_QUOTES | ENT_HTML5));
        }

        try {
            $result = $this->runScan(
                path: (string) $file->getRealPath(),
                extension: strtolower($file->getClientOriginalExtension()),
                filename: $file->getClientOriginalName(),
                jobOffer: $jobOffer,
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('scan.index')
                ->with('scan.error', $e->getMessage())
                ->with('scan.job_offer', $jobOffer)
                ->with('scan.job_offer_html', $jobOfferHtml);
        }

        return redirect()
            ->route('scan.index')
            ->with('scan.result', $result)
            ->with('scan.job_offer', $jobOffer)
            ->with('scan.job_offer_html', $jobOfferHtml);
    }

    public function api(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
            'job_offer' => ['required', 'string', 'max:50000'],
        ]);

        $file = $request->file('file');
        $jobOffer = (string) $request->input('job_offer');

        try {
            $result = $this->runScan(
                path: (string) $file->getRealPath(),
                extension: strtolower($file->getClientOriginalExtension()),
                filename: $file->getClientOriginalName(),
                jobOffer: $jobOffer,
            );
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            return response()->json(['error' => "Erreur interne lors de l'analyse: ".$e->getMessage()], 500);
        }

        return response()->json($result);
    }

    /**
     * @param  array<string, mixed>|null  $result
     */
    private function viewResult(?string $error = null, ?string $jobOffer = null, ?array $result = null, ?string $jobOfferHtml = null): View
    {
        return view('scan.index', [
            'templates' => self::TEMPLATE_LABELS,
            'templateContents' => self::TEMPLATES,
            'error' => $error,
            'jobOffer' => $jobOffer,
            'jobOfferHtml' => $jobOfferHtml,
            'result' => $result,
        ]);
    }

    private function sanitizeJobOfferHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        if (! preg_match('/<[a-z][\s\S]*>/i', $html)) {
            return e($html);
        }

        $clean = strip_tags($html, '<h2><h3><h4><p><ul><ol><li><strong><b><em><i><u><br><pre><code><div><span>');
        $clean = preg_replace('/\s*on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*style\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*class\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;

        return $clean;
    }

    /**
     * @return array{
     *     filename: string,
     *     ocr_used: bool,
     *     cv_text_preview: string,
     *     job_skills_found: list<string>,
     *     analysis: array<string, mixed>,
     *     processing_time_ms: int,
     * }
     */
    private function runScan(string $path, string $extension, string $filename, string $jobOffer): array
    {
        $startedAt = microtime(true);

        if (! in_array($extension, ['pdf', 'docx'], true)) {
            throw new RuntimeException('Formats acceptés : .pdf, .docx');
        }

        try {
            $extracted = $this->extractor->extract($path, $extension);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException('Erreur lors de l\'extraction du CV : '.$e->getMessage(), 0, $e);
        }

        $cvText = $extracted['text'];

        if (trim($cvText) === '') {
            throw new RuntimeException("Impossible d'extraire le texte du CV.");
        }

        $jobSkills = $this->skillLibrary->extractSkillsFromJob($jobOffer);
        $analysis = $this->analyzer->analyze($cvText, $jobSkills);

        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        $preview = mb_strlen($cvText) > 500 ? mb_substr($cvText, 0, 500).'...' : $cvText;

        return [
            'filename' => $filename,
            'ocr_used' => $extracted['ocr_used'],
            'cv_text_preview' => $preview,
            'job_skills_found' => $jobSkills,
            'analysis' => $analysis,
            'processing_time_ms' => $elapsedMs,
        ];
    }
}
