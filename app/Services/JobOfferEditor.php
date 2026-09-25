<?php

namespace App\Services;

class JobOfferEditor
{
    /**
     * Job offer templates ported from ProjetATS scan.component.ts (HTML for the rich editor).
     *
     * @var array<string, string>
     */
    public const TEMPLATES = [
        'support-it' => '<h2>Support Technique Informatique &amp; Administration Réseau Système</h2><h3>Compétences requises</h3><ul><li>Windows Server</li><li>Active Directory</li><li>Linux (Ubuntu, Debian)</li><li>TCP/IP</li><li>DNS</li><li>DHCP</li><li>VPN</li><li>Firewall</li><li>Virtualisation (VMware, Hyper-V)</li><li>Cisco</li><li>MikroTik</li><li>Migration de données</li></ul><h3>Missions</h3><ul><li>Installation et configuration de postes de travail</li><li>Gestion des comptes utilisateurs et des droits d\'accès</li><li>Maintenance des serveurs et infrastructures réseau</li><li>Dépannage et résolution des incidents techniques</li><li>Mise en place et administration des équipements réseau</li><li>Sauvegarde et restauration des données</li><li>Suivi des performances du réseau</li><li>Documentation technique et reporting</li></ul>',
        'dev' => '<h2>Développeur Full Stack</h2><h3>Compétences requises</h3><ul><li>Python</li><li>JavaScript</li><li>React</li><li>Node.js</li><li>PostgreSQL</li><li>Git</li></ul><h3>Missions</h3><ul><li>Développement d\'applications web</li><li>Maintenance du code existant</li><li>Participation aux revues de code</li></ul>',
        'devops' => '<h2>Ingénieur DevOps</h2><h3>Compétences requises</h3><ul><li>Docker</li><li>Kubernetes</li><li>CI/CD</li><li>AWS ou Azure</li><li>Linux</li><li>Terraform</li></ul><h3>Missions</h3><ul><li>Mise en place de pipelines CI/CD</li><li>Gestion de l\'infrastructure cloud</li><li>Automatisation du déploiement</li></ul>',
        'data' => '<h2>Data Engineer</h2><h3>Compétences requises</h3><ul><li>Python</li><li>SQL</li><li>Apache Spark</li><li>Airflow</li><li>AWS/GCP</li><li>ETL</li></ul><h3>Missions</h3><ul><li>Conception de pipelines de données</li><li>Optimisation des performances</li><li>Qualité des données</li></ul>',
    ];

    /**
     * @var array<string, string>
     */
    public const TEMPLATE_LABELS = [
        'support-it' => 'Support IT',
        'dev' => 'Développeur',
        'devops' => 'DevOps',
        'data' => 'Data',
    ];

    /**
     * @return array<string, string>
     */
    public function templateLabels(): array
    {
        return self::TEMPLATE_LABELS;
    }

    /**
     * @return array<string, string>
     */
    public function templateContents(): array
    {
        return self::TEMPLATES;
    }

    /**
     * @return array{html: string, text: string}
     */
    public function editorInitial(string $jobOffer, ?string $jobOfferHtml, string $oldHtml = '', string $oldText = ''): array
    {
        $html = $jobOfferHtml;

        if ($html === null || trim($html) === '') {
            $html = $oldHtml;
        }

        if ($html === null || trim($html) === '') {
            $html = $oldText !== '' ? $oldText : $jobOffer;
        }

        if ($html === null || trim($html) === '') {
            return ['html' => '', 'text' => ''];
        }

        if (! preg_match('/<[a-z][\s\S]*>/i', $html)) {
            $html = e($html);
        }

        return [
            'html' => $html,
            'text' => $oldText !== '' ? $oldText : $jobOffer,
        ];
    }

    public function sanitizeHtml(string $html): string
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
     * Derive plain-text job offer from editor payload when needed.
     */
    public function plainText(string $jobOffer, string $jobOfferHtml): string
    {
        if (trim($jobOffer) !== '') {
            return trim($jobOffer);
        }

        return trim(html_entity_decode(strip_tags($jobOfferHtml), ENT_QUOTES | ENT_HTML5));
    }
}
