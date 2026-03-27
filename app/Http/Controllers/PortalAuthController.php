<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class PortalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.login');
    }

    public function showRegister(): View
    {
        return view('portal.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        $raw = trim($data['identifier']);
        $digits = preg_replace('/\D+/', '', $raw ?? '');

        $user = User::query()
            ->where('user_type', 'patient')
            ->where(function ($query) use ($raw, $digits) {
                $query->where('email', $raw);

                if (filled($digits)) {
                    $query->orWhere('phone', $digits)->orWhere('document', $digits);
                }
            })
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors([
                'identifier' => 'Não foi possível autenticar com os dados informados.',
            ])->onlyInput('identifier');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'document' => ['required', 'string', 'max:20'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $phone = preg_replace('/\D+/', '', $data['phone']) ?: null;
        $whatsapp = preg_replace('/\D+/', '', $data['whatsapp'] ?? '') ?: $phone;
        $document = preg_replace('/\D+/', '', $data['document']) ?: null;
        $whatsappOptIn = (bool) ($data['whatsapp_opt_in'] ?? false);

        $user = DB::transaction(function () use ($data, $phone, $whatsapp, $document, $whatsappOptIn) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $phone,
                'document' => $document,
                'user_type' => 'patient',
                'is_active' => true,
                'password' => Hash::make($data['password']),
            ]);

            Role::findOrCreate('paciente', 'web');
            $user->assignRole('paciente');

            Patient::query()->create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'birth_date' => $data['birth_date'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'whatsapp_opt_in' => $whatsappOptIn,
                'whatsapp_opt_in_at' => $whatsappOptIn ? now() : null,
                'cpf' => $document,
                'is_active' => true,
            ]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('portal.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
