<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

#[Signature('install:app
    {--skip-composer : Skip composer install}
    {--skip-npm : Skip npm install and Vite build}
    {--skip-migrate : Skip database migrations}
    {--check : Check prerequisites only, without changing the project}')]
#[Description('Install and configure the Scanne-CV-ATS application')]
class InstallAppCommand extends Command
{
    private const REQUIRED_PHP_EXTENSIONS = [
        'fileinfo',
        'mbstring',
        'openssl',
        'pdo',
        'pdo_sqlite',
        'tokenizer',
        'xml',
    ];

    private const REQUIRED_BINARIES = [
        'unzip',
    ];

    private const OPTIONAL_BINARIES = [
        'composer',
        'node',
        'npm',
        'pdftoppm',
        'tesseract',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Scanne-CV-ATS — installation');

        if (! $this->checkPrerequisites()) {
            return self::FAILURE;
        }

        if ($this->option('check')) {
            $this->components->info('Prerequisites OK (check only, nothing changed).');

            return self::SUCCESS;
        }

        if (! $this->ensureEnvFile()) {
            return self::FAILURE;
        }

        if (! $this->ensureAppKey()) {
            return self::FAILURE;
        }

        if (! $this->ensureSqliteDatabase()) {
            return self::FAILURE;
        }

        if (! $this->option('skip-composer') && ! $this->installComposerDependencies()) {
            return self::FAILURE;
        }

        if (! $this->option('skip-migrate') && ! $this->migrateDatabase()) {
            return self::FAILURE;
        }

        if (! $this->option('skip-npm') && ! $this->buildFrontend()) {
            return self::FAILURE;
        }

        $this->clearLocalCaches();

        $this->newLine();
        $this->components->info('Installation terminée.');

        return self::SUCCESS;
    }

    private function checkPrerequisites(): bool
    {
        $ok = true;

        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.3.0', '<')) {
            $this->components->error(sprintf('PHP %s >= 8.3.0 requis (actuel : %s).', '8.3.0', $phpVersion));
            $ok = false;
        } else {
            $this->components->twoColumnDetail('PHP', $phpVersion);
        }

        foreach (self::REQUIRED_PHP_EXTENSIONS as $extension) {
            if (! extension_loaded($extension)) {
                $this->components->error(sprintf('Extension PHP manquante : %s', $extension));
                $ok = false;
            }
        }

        foreach (self::REQUIRED_BINARIES as $binary) {
            if (! $this->binaryExists($binary)) {
                $this->components->error(sprintf('Binaire manquant : %s', $binary));
                $ok = false;
            } else {
                $this->components->twoColumnDetail($binary, 'OK');
            }
        }

        foreach (self::OPTIONAL_BINARIES as $binary) {
            if ($this->binaryExists($binary)) {
                $this->components->twoColumnDetail($binary, 'OK');
            } else {
                $this->components->twoColumnDetail($binary, '<comment>absent (optionnel)</comment>');
            }
        }

        if (! $this->binaryExists('tesseract')) {
            $this->components->warn('Tesseract absent : l’OCR des PDF scannés sera indisponible.');
        }

        if (! $this->binaryExists('pdftoppm')) {
            $this->components->warn('pdftoppm absent : conversion PDF → image pour l’OCR indisponible.');
        }

        return $ok;
    }

    private function ensureEnvFile(): bool
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (is_file($envPath)) {
            $this->components->twoColumnDetail('.env', 'déjà présent');

            return true;
        }

        if (! is_file($examplePath)) {
            $this->components->error('.env.example introuvable.');

            return false;
        }

        if (! copy($examplePath, $envPath)) {
            $this->components->error('Impossible de créer .env depuis .env.example.');

            return false;
        }

        $this->components->twoColumnDetail('.env', 'créé depuis .env.example');

        return true;
    }

    private function ensureAppKey(): bool
    {
        if ($this->envFileHasAppKey()) {
            $this->components->twoColumnDetail('APP_KEY', 'déjà définie');

            return true;
        }

        // key:generate replaces APP_KEY=<current config key>; clear it so an empty
        // APP_KEY= line in a freshly copied .env can be filled (process may still
        // hold a key from a previous boot).
        config(['app.key' => '']);
        putenv('APP_KEY');
        unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);

        $exitCode = $this->callSilent('key:generate', ['--force' => true]);
        if ($exitCode !== 0 || ! $this->envFileHasAppKey()) {
            if (! $this->writeAppKeyToEnvFile()) {
                $this->components->error('Échec de key:generate.');

                return false;
            }
        }

        $this->components->twoColumnDetail('APP_KEY', 'générée');

        return true;
    }

    private function writeAppKeyToEnvFile(): bool
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            return false;
        }

        $key = 'base64:'.base64_encode(random_bytes(32));
        $contents = (string) file_get_contents($envPath);

        if (preg_match('/^APP_KEY=.*$/m', $contents) === 1) {
            $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents);
        } else {
            $contents = rtrim($contents).PHP_EOL.'APP_KEY='.$key.PHP_EOL;
        }

        if ($contents === null || file_put_contents($envPath, $contents) === false) {
            return false;
        }

        config(['app.key' => $key]);

        return $this->envFileHasAppKey();
    }

    private function envFileHasAppKey(): bool
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            return false;
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with($line, 'APP_KEY=')) {
                return trim(substr($line, strlen('APP_KEY=')), " \t\"'") !== '';
            }
        }

        return false;
    }

    private function ensureSqliteDatabase(): bool
    {
        $connection = (string) config('database.default');

        if ($connection !== 'sqlite') {
            $this->components->twoColumnDetail('base de données', $connection);

            return true;
        }

        $database = (string) config('database.connections.sqlite.database');

        if ($database === ':memory:' || $database === '') {
            return true;
        }

        if (is_file($database)) {
            $this->components->twoColumnDetail('SQLite', $database);

            return true;
        }

        $directory = dirname($database);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            $this->components->error(sprintf('Dossier SQLite inaccessible : %s', $directory));

            return false;
        }

        if (touch($database) === false) {
            $this->components->error(sprintf('Impossible de créer la base SQLite : %s', $database));

            return false;
        }

        $this->components->twoColumnDetail('SQLite', 'créée — '.$database);

        return true;
    }

    private function installComposerDependencies(): bool
    {
        if (! $this->binaryExists('composer')) {
            $this->components->error('Composer introuvable — installez-le puis relancez.');

            return false;
        }

        if (is_file(base_path('vendor/autoload.php'))) {
            $this->components->twoColumnDetail('composer', 'vendor/ déjà installé (utilisé tel quel)');

            return true;
        }

        $this->components->task('composer install', function (): bool {
            $result = Process::run('composer install --no-interaction --prefer-dist');

            return $result->successful();
        });

        if (! is_file(base_path('vendor/autoload.php'))) {
            $this->components->error('composer install a échoué (vendor/autoload.php absent).');

            return false;
        }

        return true;
    }

    private function migrateDatabase(): bool
    {
        $exitCode = $this->call('migrate', ['--force' => true]);

        if ($exitCode !== 0) {
            $this->components->error('Les migrations ont échoué.');

            return false;
        }

        $this->components->twoColumnDetail('migrations', 'OK');

        return true;
    }

    private function buildFrontend(): bool
    {
        if (! $this->binaryExists('npm')) {
            $this->components->error('npm introuvable — installez Node.js puis relancez.');

            return false;
        }

        if (! is_dir(base_path('node_modules'))) {
            $installed = Process::run('npm install --ignore-scripts --no-audit --no-fund');
            if (! $installed->successful()) {
                $this->components->error('npm install a échoué.');
                $this->line($installed->errorOutput());

                return false;
            }
        }

        $this->components->task('npm run build', function (): bool {
            $result = Process::run('npm run build');

            return $result->successful();
        });

        if (! is_file(base_path('public/build/manifest.json'))) {
            $this->components->error('Vite build a échoué (public/build/manifest.json absent).');

            return false;
        }

        return true;
    }

    private function clearLocalCaches(): void
    {
        $this->callSilent('config:clear');
        $this->callSilent('view:clear');
        $this->callSilent('route:clear');
        $this->components->twoColumnDetail('caches locales', 'effacées');
    }

    private function binaryExists(string $binary): bool
    {
        $result = Process::run(sprintf('command -v %s', escapeshellarg($binary)));

        return $result->successful() && trim($result->output()) !== '';
    }
}
