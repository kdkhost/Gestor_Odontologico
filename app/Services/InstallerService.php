<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PDO;
use Spatie\Permission\Models\Role;
use Throwable;

class InstallerService
{
    public function lockPath(): string
    {
        return storage_path('app/installer/installed.lock');
    }

    public function isInstalled(): bool
    {
        return File::exists($this->lockPath());
    }

    public function requirements(): array
    {
        $phpOk = version_compare(PHP_VERSION, '8.4.0', '>=');

        $extensions = [
            ['label' => 'pdo', 'ok' => extension_loaded('pdo'), 'required' => true],
            ['label' => 'pdo_mysql', 'ok' => extension_loaded('pdo_mysql'), 'required' => true],
            ['label' => 'openssl', 'ok' => extension_loaded('openssl'), 'required' => true],
            ['label' => 'mbstring', 'ok' => extension_loaded('mbstring'), 'required' => true],
            ['label' => 'json', 'ok' => extension_loaded('json'), 'required' => true],
            ['label' => 'fileinfo', 'ok' => extension_loaded('fileinfo'), 'required' => true],
            ['label' => 'curl', 'ok' => extension_loaded('curl'), 'required' => true],
            ['label' => 'gd', 'ok' => extension_loaded('gd'), 'required' => true],
            ['label' => 'zip', 'ok' => extension_loaded('zip'), 'required' => true],
            ['label' => 'pdo_sqlite (opcional para SQLite local)', 'ok' => extension_loaded('pdo_sqlite'), 'required' => false],
        ];

        $paths = [
            ['label' => 'storage', 'path' => storage_path(), 'ok' => is_writable(storage_path())],
            ['label' => 'bootstrap_cache', 'path' => base_path('bootstrap/cache'), 'ok' => is_writable(base_path('bootstrap/cache'))],
        ];

        return [
            'ready' => $phpOk
                && collect($extensions)->every(fn (array $item) => ! $item['required'] || $item['ok'])
                && collect($paths)->every(fn (array $item) => $item['ok']),
            'php' => [
                'current' => PHP_VERSION,
                'required' => '8.4+',
                'ok' => $phpOk,
            ],
            'extensions' => $extensions,
            'paths' => $paths,
        ];
    }

    public function validateEnvironmentReady(): void
    {
        if (! $this->requirements()['ready']) {
            throw new \RuntimeException('O ambiente atual não atende aos requisitos mínimos do sistema.');
        }
    }

    public function testDatabaseConnection(array $data): array
    {
        try {
            if ($data['db_connection'] === 'sqlite') {
                $database = $data['db_database'] ?? database_path('database.sqlite');

                File::ensureDirectoryExists(dirname($database));

                if (! File::exists($database)) {
                    File::put($database, '');
                }

                new PDO('sqlite:'.$database);

                return ['ok' => true, 'message' => 'Arquivo SQLite validado com sucesso.'];
            }

            $driver = in_array($data['db_connection'], ['mysql', 'mariadb'], true) ? 'mysql' : $data['db_connection'];
            $host = $data['db_host'] ?? '127.0.0.1';
            $port = $data['db_port'] ?? '3306';
            $database = $data['db_database'] ?? '';
            $username = $data['db_username'] ?? '';
            $password = $data['db_password'] ?? '';

            new PDO(
                "{$driver}:host={$host};port={$port};dbname={$database}",
                $username,
                $password,
                [
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ],
            );

            return ['ok' => true, 'message' => 'Conexão com o banco validada com sucesso.'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
    }

    public function install(array $data): void
    {
        $this->validateEnvironmentReady();

        $databaseCheck = $this->testDatabaseConnection($data);

        if (! $databaseCheck['ok']) {
            throw new \RuntimeException('Não foi possível validar a conexão com o banco informado.');
        }

        $env = [
            'APP_NAME' => $data['app_name'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $data['app_url'],
            'APP_LOCALE' => 'pt_BR',
            'APP_FALLBACK_LOCALE' => 'pt_BR',
            'APP_FAKER_LOCALE' => 'pt_BR',
            'APP_TIMEZONE' => 'America/Sao_Paulo',
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'] ?? '127.0.0.1',
            'DB_PORT' => $data['db_port'] ?? '3306',
            'DB_DATABASE' => $data['db_database'] ?? database_path('database.sqlite'),
            'DB_USERNAME' => $data['db_username'] ?? '',
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ];

        if ($data['db_connection'] === 'sqlite') {
            File::ensureDirectoryExists(database_path());

            if (! File::exists($env['DB_DATABASE'])) {
                File::put($env['DB_DATABASE'], '');
            }
        }

        $this->writeEnvironment($env);
        $this->applyRuntimeConfiguration($env);

        if (blank(config('app.key')) && blank(env('APP_KEY'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        Artisan::call('optimize:clear');
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        try {
            Artisan::call('storage:link');
        } catch (Throwable) {
            // Ignora quando o link já existe ou a hospedagem bloqueia symlink.
        }

        if (! Schema::hasTable('units') || ! Schema::hasTable('users')) {
            return;
        }

        $unit = Unit::query()->firstOrCreate(
            ['slug' => Str::slug($data['unit_name'])],
            [
                'name' => $data['unit_name'],
                'legal_name' => $data['unit_name'],
                'email' => $data['admin_email'],
                'phone' => $data['admin_phone'] ?? null,
                'is_active' => true,
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => $data['admin_email']],
            [
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'phone' => $data['admin_phone'] ?? null,
                'document' => $data['admin_document'] ?? null,
                'unit_id' => $unit->id,
                'user_type' => 'staff',
                'is_active' => true,
                'password' => Hash::make($data['admin_password']),
            ],
        );

        Role::findOrCreate('superadmin', 'web');
        $admin->syncRoles(['superadmin']);

        SystemSetting::query()->updateOrCreate(
            ['unit_id' => null, 'group' => 'branding', 'key' => 'app_name'],
            ['type' => 'string', 'value' => $data['app_name'], 'is_public' => true],
        );

        SystemSetting::query()->updateOrCreate(
            ['unit_id' => null, 'group' => 'maintenance', 'key' => 'enabled'],
            ['type' => 'boolean', 'value' => '0', 'is_public' => false],
        );

        File::ensureDirectoryExists(dirname($this->lockPath()));
        File::put($this->lockPath(), json_encode([
            'installed_at' => now()->toIso8601String(),
            'unit_id' => $unit->id,
            'admin_email' => $admin->email,
        ], JSON_PRETTY_PRINT));
    }

    private function applyRuntimeConfiguration(array $env): void
    {
        config([
            'app.name' => $env['APP_NAME'],
            'app.url' => $env['APP_URL'],
            'database.default' => $env['DB_CONNECTION'],
            "database.connections.{$env['DB_CONNECTION']}.database" => $env['DB_DATABASE'],
            "database.connections.{$env['DB_CONNECTION']}.host" => $env['DB_HOST'],
            "database.connections.{$env['DB_CONNECTION']}.port" => $env['DB_PORT'],
            "database.connections.{$env['DB_CONNECTION']}.username" => $env['DB_USERNAME'],
            "database.connections.{$env['DB_CONNECTION']}.password" => $env['DB_PASSWORD'],
        ]);
    }

    private function writeEnvironment(array $pairs): void
    {
        $path = base_path('.env');
        $contents = File::exists($path) ? File::get($path) : '';

        foreach ($pairs as $key => $value) {
            $escaped = $this->normalizeEnvironmentValue($value);
            $pattern = "/^{$key}=.*$/m";

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, "{$key}={$escaped}", $contents) ?? $contents;
            } else {
                $contents .= PHP_EOL."{$key}={$escaped}";
            }
        }

        File::put($path, trim($contents).PHP_EOL);
    }

    private function normalizeEnvironmentValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        if ($value === '' || preg_match('/\s/', $value)) {
            return '"'.addcslashes($value, '"').'"';
        }

        return $value;
    }
}
