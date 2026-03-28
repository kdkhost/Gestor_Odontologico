<?php

namespace App\Http\Controllers;

use App\Services\InstallerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    public function __construct(private readonly InstallerService $installer) {}

    public function index(): View|RedirectResponse
    {
        if ($this->installer->isInstalled()) {
            return redirect()->route('home');
        }

        return view('install.index', [
            'requirements' => $this->installer->requirements(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url'],
            'unit_name' => ['required', 'string', 'max:120'],
            'db_connection' => ['required', 'in:sqlite,mysql,mariadb'],
            'db_host' => ['nullable', 'string', 'max:120'],
            'db_port' => ['nullable', 'string', 'max:10'],
            'db_database' => ['nullable', 'string', 'max:255'],
            'db_username' => ['nullable', 'string', 'max:120'],
            'db_password' => ['nullable', 'string', 'max:120'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:120'],
            'admin_phone' => ['nullable', 'string', 'max:20'],
            'admin_document' => ['nullable', 'string', 'max:20'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! $this->installer->requirements()['ready']) {
            return back()
                ->withErrors(['requirements' => 'O servidor não atende aos requisitos mínimos do sistema.'])
                ->withInput();
        }

        $databaseCheck = $this->installer->testDatabaseConnection($data);

        if (! $databaseCheck['ok']) {
            return back()
                ->withErrors(['db_connection' => $databaseCheck['message']])
                ->withInput();
        }

        try {
            $this->installer->install($data);
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['install' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('admin.login')
            ->with('status', 'Sistema instalado com sucesso. Faça login com o superadmin criado.');
    }
}
