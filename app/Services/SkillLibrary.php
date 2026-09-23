<?php

namespace App\Services;

class SkillLibrary
{
    /**
     * Canonical tech skill list from ProjetATS scan.py (TECH_SKILLS).
     *
     * @var list<string>
     */
    public const TECH_SKILLS = [
        'python', 'java', 'javascript', 'typescript', 'angular', 'react', 'vue',
        'node.js', 'php', 'c++', 'c#', 'ruby', 'go', 'golang', 'rust', 'swift',
        'kotlin', 'scala', 'dart', 'elixir', 'haskell', 'perl', 'lua', 'r',
        'docker', 'kubernetes', 'k8s', 'ansible', 'terraform', 'jenkins',
        'gitlab', 'github', 'webpack', 'vite', 'npm', 'yarn', 'pip',
        'html', 'css', 'scss', 'sass', 'less', 'tailwind', 'bootstrap',
        'mysql', 'postgresql', 'postgres', 'mongodb', 'redis', 'elasticsearch',
        'sqlite', 'mariadb', 'oracle', 'cassandra', 'dynamodb', 'neo4j',
        'aws', 'azure', 'gcp', 'heroku', 'digitalocean', 'cloudflare', 'vercel', 'netlify',
        'linux', 'ubuntu', 'debian', 'centos', 'redhat', 'fedora', 'windows',
        'rest', 'graphql', 'grpc', 'api', 'soap', 'websocket',
        'agile', 'scrum', 'kanban', 'sprint', 'jira', 'confluence', 'trello',
        'ci/cd', 'devops', 'sre', 'pipeline',
        'tensorflow', 'pytorch', 'keras', 'pandas', 'numpy', 'opencv',
        'spacy', 'nltk', 'scikit-learn',
        'django', 'flask', 'fastapi', 'spring', 'laravel', 'rails', 'express',
        'next.js', 'nuxt', 'nestjs',
        'figma', 'sketch', 'photoshop', 'illustrator',
        'flutter', 'react native', 'xamarin', 'maui', 'ionic',
        'seo', 'google analytics', 'google ads',
        'jest', 'mocha', 'pytest', 'selenium', 'cypress', 'playwright',
        'git', 'svn',
        'active directory', 'oauth', 'jwt', 'sso', 'saml', 'ldap',
        'vpn', 'firewall', 'dns', 'ssh', 'ssl', 'tls',
        'proxmox', 'vmware', 'hyper-v', 'kvm',
        'nginx', 'apache', 'tomcat', 'iis',
        'kafka', 'rabbitmq', 'celery', 'nats',
        'excel', 'power bi', 'tableau',
        'shopify', 'woocommerce', 'magento', 'wordpress',
        'cloudflare', 'cdn',
        'notion', 'slack', 'teams', 'discord',
        'openai', 'langchain', 'llm', 'nlp', 'rag',
        'machine learning', 'deep learning', 'ai',
        'sql', 'nosql', 'plsql',
        'svg', 'xml', 'json', 'yaml',
        'prometheus', 'grafana', 'datadog', 'sentry', 'zabbix',
        'hadoop', 'spark', 'flink', 'airflow',
        'databricks', 'snowflake', 'bigquery', 'redshift',
        'jupyter', 'colab',
        'unity', 'unreal', 'godot', 'blender',
        'web3', 'blockchain', 'solidity',
        'traefik', 'haproxy', 'envoy', 'istio',
        'podman', 'containerd',
        'pulumi', 'cloudformation', 'helm', 'kustomize',
        'visual studio', 'vscode', 'intellij', 'pycharm',
        'android studio', 'xcode',
        'maya', 'cinema 4d', 'premiere', 'after effects', 'davinci',
        'salesforce', 'hubspot', 'zendesk',
        'stripe', 'paypal',
        'strapi', 'payload', 'directus',
        'openapi', 'swagger', 'postman',
        'pwa', 'webassembly', 'wasm',
        'three.js', 'd3.js', 'chart.js',
        'microservices', 'monolith', 'serverless',
        'cqrs', 'event sourcing', 'event driven',
        'solid', 'clean code', 'refactoring',
        'pair programming', 'code review',
        'load testing', 'stress testing', 'benchmark',
        'lighthouse', 'core web vitals',
        'owasp', 'gdpr', 'hipaa', 'soc 2', 'iso 27001',
        'product management', 'product owner',
        'scrum master', 'agile coach',
        'tech lead', 'engineering manager', 'cto',
        'architect', 'solution architect',
        'data engineer', 'data scientist',
        'ml engineer', 'ai engineer',
        'backend developer', 'frontend developer', 'full stack',
        'mobile developer', 'cloud engineer', 'security engineer',
        'qa engineer', 'test engineer',
        'ux designer', 'ui designer', 'ux researcher',
        'technical writer', 'devrel',
        'firestore', 'supabase', 'appwrite', 'firebase',
        'etcd', 'zookeeper', 'consul', 'vault',
        'packer', 'vagrant',
        'openshift', 'rancher', 'linkerd',
        'minikube', 'k3s', 'microk8s',
        'gunicorn', 'uvicorn',
        'pydantic', 'sqlalchemy', 'alembic',
        'keycloak', 'auth0', 'okta',
        'sonarqube', 'eslint', 'prettier', 'mypy',
        'tox', 'coverage',
        'circleci', 'travis', 'drone', 'buildkite',
        'bash', 'zsh', 'powershell',
        'vb.net', 'asp.net',
        'couchdb', 'couchbase', 'scylla', 'cosmosdb',
        'memcached',
        'ghost', 'bigcommerce', 'prestashop',
        'sinatra', 'starlette', 'fastify', 'koa',
        'symfony', 'codeigniter',
        'springboot', 'quarkus', 'micronaut',
        'linode', 'vultr',
        'lambda', 'rds', 'sqs', 'sns', 'ec2', 's3', 'cloudwatch',
        'gke', 'aks', 'eks',
        'logstash', 'filebeat', 'fluentd',
        'newrelic', 'bugsnag', 'rollbar',
        'pagerduty', 'opsgenie',
        'd3', 'leaflet', 'mapbox',
        'mapbox', 'webrtc',
        'hls', 'dash',
        'ffmpeg', 'gstreamer',
        'podcast', 'streaming',
        'opentelemetry', 'otel',
        'certbot',
        'docker compose',
        'junior', 'senior', 'mid', 'staff', 'principal',
        'intern', 'trainee',
        'manager', 'director', 'lead',
        'consultant', 'analyst', 'specialist',
        'mongodb', 'sql server',
        'bitbucket',
        'terraform', 'ansible', 'puppet', 'chef',
        'github actions', 'gitlab ci',
        'argocd', 'tekton', 'flux',
        'spinnaker',
        'story point', 'velocity', 'backlog',
        'retrospective', 'standup',
        'burndown', 'burnup',
        'lean', 'six sigma',
        'itil', 'cobit',
        'togaf',
        'wireframe', 'prototype', 'usability',
        'accessibility', 'wcag',
        'responsive', 'mobile first',
        'spa', 'ssr', 'ssg', 'isr',
        'monorepo',
        'feature flag', 'a/b testing',
        'funnel', 'retention', 'churn',
        'kpi', 'metric', 'dashboard',
        'conversion', 'revenue',
        'nps', 'csat',
        'okr',
        'portfolio', 'linkedin',
        'mentorship', 'coaching',
        'leadership', 'communication',
        'remote work', 'freelancing',
        'startup',
        'etl', 'elt',
        'data pipeline', 'data lake', 'data warehouse',
        'apache kafka', 'apache spark', 'apache airflow',
        'dbt',
        'mlops', 'dataops',
        'transformer', 'bert', 'gpt',
        'stable diffusion', 'midjourney',
        'ollama', 'vllm',
        'langchain', 'llamaindex',
        'maven', 'gradle',
        'power shell',
        'migration de données',
        'tcp/ip',
    ];

    /** @var array<string, list<string>> */
    private array $synonyms = [];

    /** @var array<string, string> */
    private array $synonymReverse = [];

    public function __construct(?string $synonymsPath = null)
    {
        $path = $synonymsPath ?? dirname(__DIR__, 2).'/resources/data/synonyms.json';

        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);

            if (is_array($decoded)) {
                $this->synonyms = $decoded;
            }
        }

        foreach ($this->synonyms as $canonical => $syns) {
            foreach ($syns as $synonym) {
                $this->synonymReverse[mb_strtolower((string) $synonym)] = mb_strtolower((string) $canonical);
            }

            $this->synonymReverse[mb_strtolower((string) $canonical)] = mb_strtolower((string) $canonical);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function synonyms(): array
    {
        return $this->synonyms;
    }

    public function normalizeSkill(string $skill): string
    {
        $normalized = mb_strtolower(trim($skill));

        return $this->synonymReverse[$normalized] ?? $normalized;
    }

    /**
     * Extract skills present in a job offer (uppercase, like ProjetATS).
     *
     * @return list<string>
     */
    public function extractSkillsFromJob(string $jobContent): array
    {
        $found = [];
        $contentLower = mb_strtolower($jobContent);

        foreach (self::TECH_SKILLS as $skill) {
            if ($this->matchSkillInCv($skill, $contentLower)) {
                $found[] = mb_strtoupper($skill);
            }
        }

        return array_values(array_unique($found));
    }

    public function matchSkillInCv(string $skill, string $cvTextLower): bool
    {
        $pattern = '/\b'.preg_quote(mb_strtolower($skill), '/').'\b/u';

        return (bool) preg_match($pattern, $cvTextLower);
    }

    /**
     * Detect all skills present in a CV, including via synonyms (uppercase).
     *
     * @return list<string>
     */
    public function detectSkillsInCv(string $cvTextLower): array
    {
        $found = [];

        foreach (self::TECH_SKILLS as $skill) {
            if ($this->matchSkillInCv($skill, $cvTextLower)) {
                $found[] = mb_strtoupper($skill);
            }
        }

        $foundUpper = array_map(mb_strtoupper(...), $found);

        foreach ($this->synonyms as $canonical => $syns) {
            if (in_array(mb_strtoupper((string) $canonical), $foundUpper, true)) {
                continue;
            }

            foreach ($syns as $synonym) {
                if ($this->matchSkillInCv((string) $synonym, $cvTextLower)) {
                    $found[] = mb_strtoupper((string) $canonical);
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Multi-level matching: exact → synonym → related → partial.
     *
     * @param  list<string>  $cvSkillsDetected
     * @return array{0: bool, 1: string}
     */
    public function multiLevelMatch(string $skill, string $cvTextLower, array $cvSkillsDetected): array
    {
        $skillLower = mb_strtolower($skill);

        if ($this->matchSkillInCv($skill, $cvTextLower)) {
            return [true, 'exact'];
        }

        $canonical = $this->normalizeSkill($skill);

        if ($canonical !== $skillLower) {
            foreach ($this->synonyms[$canonical] ?? [] as $synonym) {
                if ($this->matchSkillInCv($synonym, $cvTextLower)) {
                    return [true, 'synonym('.$synonym.')'];
                }
            }
        }

        foreach ($this->synonyms as $canonicalKey => $syns) {
            $allForms = [(string) $canonicalKey, ...$syns];
            $skillFormsLower = array_map(mb_strtolower(...), $allForms);

            if (! in_array($skillLower, $skillFormsLower, true)) {
                continue;
            }

            foreach ($allForms as $form) {
                if (mb_strtolower($form) !== $skillLower && $this->matchSkillInCv($form, $cvTextLower)) {
                    return [true, 'synonym('.$form.')'];
                }
            }
        }

        foreach ($cvSkillsDetected as $detected) {
            $detectedLower = mb_strtolower($detected);

            if ($this->normalizeSkill($detectedLower) === $canonical) {
                return [true, 'related('.$detected.')'];
            }

            if ($this->normalizeSkill($detectedLower) === $skillLower) {
                return [true, 'related('.$detected.')'];
            }
        }

        foreach ($cvSkillsDetected as $cvSkill) {
            $cvLower = mb_strtolower($cvSkill);

            if (str_contains($skillLower, $cvLower) || str_contains($cvLower, $skillLower)) {
                $shortest = mb_strlen($skillLower) <= mb_strlen($cvLower) ? $skillLower : $cvLower;

                if (mb_strlen($shortest) >= 3) {
                    return [true, 'partial('.$cvSkill.')'];
                }
            }
        }

        return [false, 'none'];
    }
}
