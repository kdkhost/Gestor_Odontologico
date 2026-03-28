<?php

namespace Tests\Unit;

use App\Support\InstallerBootstrap;
use Tests\TestCase;

class InstallerBootstrapTest extends TestCase
{
    private ?string $originalAppKey = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAppKey = getenv('APP_KEY') !== false ? getenv('APP_KEY') : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalAppKey === null) {
            putenv('APP_KEY');
            unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);
        } else {
            putenv('APP_KEY='.$this->originalAppKey);
            $_ENV['APP_KEY'] = $this->originalAppKey;
            $_SERVER['APP_KEY'] = $this->originalAppKey;
        }

        parent::tearDown();
    }

    public function test_it_applies_temporary_key_when_system_is_not_installed_and_no_key_exists(): void
    {
        $basePath = storage_path('framework/testing/installer-bootstrap/no-key');
        $envPath = $basePath.DIRECTORY_SEPARATOR.'.env';
        $lockPath = $basePath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'installer'.DIRECTORY_SEPARATOR.'installed.lock';

        $this->cleanupFixture($basePath);
        mkdir($basePath, 0777, true);

        putenv('APP_KEY');
        unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);

        $applied = InstallerBootstrap::applyTemporaryAppKeyIfNeeded($basePath, $envPath, $lockPath);

        $this->assertTrue($applied);
        $this->assertSame(InstallerBootstrap::temporaryKeyFor($basePath), $_ENV['APP_KEY']);
        $this->assertSame(InstallerBootstrap::temporaryKeyFor($basePath), $_SERVER['APP_KEY']);

        $this->cleanupFixture($basePath);
    }

    public function test_it_does_not_apply_temporary_key_when_install_lock_exists(): void
    {
        $basePath = storage_path('framework/testing/installer-bootstrap/installed');
        $envPath = $basePath.DIRECTORY_SEPARATOR.'.env';
        $lockPath = $basePath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'installer'.DIRECTORY_SEPARATOR.'installed.lock';

        $this->cleanupFixture($basePath);
        mkdir(dirname($lockPath), 0777, true);
        file_put_contents($lockPath, json_encode(['installed_at' => now()->toIso8601String()]));

        putenv('APP_KEY');
        unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);

        $applied = InstallerBootstrap::applyTemporaryAppKeyIfNeeded($basePath, $envPath, $lockPath);

        $this->assertFalse($applied);
        $this->assertArrayNotHasKey('APP_KEY', $_ENV);
        $this->assertArrayNotHasKey('APP_KEY', $_SERVER);

        $this->cleanupFixture($basePath);
    }

    public function test_it_does_not_apply_temporary_key_when_env_file_already_has_app_key(): void
    {
        $basePath = storage_path('framework/testing/installer-bootstrap/with-env-key');
        $envPath = $basePath.DIRECTORY_SEPARATOR.'.env';
        $lockPath = $basePath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'installer'.DIRECTORY_SEPARATOR.'installed.lock';

        $this->cleanupFixture($basePath);
        mkdir($basePath, 0777, true);
        file_put_contents($envPath, "APP_KEY=base64:chave-ja-existente\n");

        putenv('APP_KEY');
        unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);

        $applied = InstallerBootstrap::applyTemporaryAppKeyIfNeeded($basePath, $envPath, $lockPath);

        $this->assertFalse($applied);
        $this->assertArrayNotHasKey('APP_KEY', $_ENV);
        $this->assertArrayNotHasKey('APP_KEY', $_SERVER);

        $this->cleanupFixture($basePath);
    }

    private function cleanupFixture(string $basePath): void
    {
        if (! is_dir($basePath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($basePath);
    }
}
