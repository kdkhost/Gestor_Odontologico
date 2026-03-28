<?php

namespace App\Support;

class InstallerBootstrap
{
    public static function applyTemporaryAppKeyIfNeeded(
        string $basePath,
        ?string $envPath = null,
        ?string $installLockPath = null,
    ): bool {
        $envPath ??= $basePath.DIRECTORY_SEPARATOR.'.env';
        $installLockPath ??= $basePath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'installer'.DIRECTORY_SEPARATOR.'installed.lock';

        if (! self::shouldApplyTemporaryAppKey($envPath, $installLockPath)) {
            return false;
        }

        $temporaryKey = self::temporaryKeyFor($basePath);
        self::disableStaleBootstrapCaches($basePath);

        $_ENV['APP_KEY'] = $temporaryKey;
        $_SERVER['APP_KEY'] = $temporaryKey;

        putenv('APP_KEY='.$temporaryKey);

        return true;
    }

    public static function shouldApplyTemporaryAppKey(
        string $envPath,
        string $installLockPath,
        ?array $server = null,
        ?array $env = null,
    ): bool {
        $server ??= $_SERVER;
        $env ??= $_ENV;

        if (is_file($installLockPath)) {
            return false;
        }

        if (self::hasRuntimeAppKey($server, $env)) {
            return false;
        }

        return ! self::hasPersistentAppKey($envPath);
    }

    public static function temporaryKeyFor(string $basePath): string
    {
        return 'base64:'.base64_encode(hash('sha256', $basePath.'|gestor-odontologico-installer', true));
    }

    public static function disableStaleBootstrapCaches(string $basePath): void
    {
        $cacheBase = $basePath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'installer-bootstrap-cache';

        if (! is_dir($cacheBase)) {
            @mkdir($cacheBase, 0777, true);
        }

        $map = [
            'APP_CONFIG_CACHE' => 'config.php',
            'APP_ROUTES_CACHE' => 'routes.php',
        ];

        foreach ($map as $key => $filename) {
            $path = $cacheBase.DIRECTORY_SEPARATOR.$filename;

            $_ENV[$key] = $path;
            $_SERVER[$key] = $path;

            putenv($key.'='.$path);
        }
    }

    public static function hasPersistentAppKey(string $envPath): bool
    {
        if (! is_file($envPath) || ! is_readable($envPath)) {
            return false;
        }

        $contents = file_get_contents($envPath);

        if ($contents === false) {
            return false;
        }

        if (! preg_match('/^APP_KEY=(.*)$/m', $contents, $matches)) {
            return false;
        }

        $value = trim($matches[1]);

        if ($value === '') {
            return false;
        }

        $value = trim($value, "\"'");

        return $value !== '';
    }

    public static function hasRuntimeAppKey(?array $server = null, ?array $env = null): bool
    {
        $server ??= $_SERVER;
        $env ??= $_ENV;

        $candidates = [
            $env['APP_KEY'] ?? null,
            $server['APP_KEY'] ?? null,
            getenv('APP_KEY') ?: null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return true;
            }
        }

        return false;
    }
}
