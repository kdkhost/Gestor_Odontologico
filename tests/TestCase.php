<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function installerLockPath(): string
    {
        return storage_path('app/installer/installed.lock');
    }

    protected function markApplicationAsInstalled(): void
    {
        File::ensureDirectoryExists(dirname($this->installerLockPath()));
        File::put($this->installerLockPath(), json_encode(['installed_at' => now()->toIso8601String()]));
    }

    protected function clearInstallationState(): void
    {
        File::delete($this->installerLockPath());
    }

    protected function tearDown(): void
    {
        $this->clearInstallationState();

        parent::tearDown();
    }
}
