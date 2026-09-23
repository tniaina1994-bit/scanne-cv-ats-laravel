<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstallAppCommandTest extends TestCase
{
    public function test_install_command_is_registered(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('install:app')
            ->assertExitCode(0);
    }

    public function test_check_mode_reports_prerequisites_without_changing_project(): void
    {
        $envPath = base_path('.env');
        $envExisted = is_file($envPath);
        $envContents = $envExisted ? file_get_contents($envPath) : null;

        $this->artisan('install:app', ['--check' => true])
            ->assertExitCode(0);

        $this->assertSame($envExisted, is_file($envPath));
        if ($envExisted) {
            $this->assertSame($envContents, file_get_contents($envPath));
        }
    }

    public function test_install_with_skipped_steps_is_idempotent(): void
    {
        $this->artisan('install:app', [
            '--skip-composer' => true,
            '--skip-npm' => true,
            '--skip-migrate' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(base_path('.env'));
        $this->assertMatchesRegularExpression(
            '/^APP_KEY=.+$/m',
            (string) file_get_contents(base_path('.env'))
        );
    }

    public function test_install_creates_env_file_when_missing(): void
    {
        $envPath = base_path('.env');
        $backupPath = base_path('.env.install-test-backup');
        $hadEnv = is_file($envPath);

        if ($hadEnv) {
            $this->assertTrue(rename($envPath, $backupPath));
        }

        try {
            $this->artisan('install:app', [
                '--skip-composer' => true,
                '--skip-npm' => true,
                '--skip-migrate' => true,
            ])->assertExitCode(0);

            $this->assertFileExists($envPath);
            $envContents = (string) file_get_contents($envPath);
            $this->assertMatchesRegularExpression('/^APP_KEY=.+$/m', $envContents);
            $this->assertDoesNotMatchRegularExpression('/^APP_KEY=\s*$/m', $envContents);
        } finally {
            if (is_file($envPath)) {
                @unlink($envPath);
            }
            if (is_file($backupPath)) {
                rename($backupPath, $envPath);
            }
        }
    }
}
