<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->user()?->canAccessAdminArea()) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        if ($request->user()?->canAccessAdminArea()) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->user()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:120'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $throttleKey = $this->throttleKey($request, $data['login']);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors([
                    'login' => "Muitas tentativas seguidas. Aguarde {$seconds} segundos e tente novamente.",
                ])
                ->onlyInput('login');
        }

        $user = $this->resolveUser($data['login']);

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->canAccessAdminArea()) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors([
                    'login' => 'Credenciais invalidas ou acesso administrativo nao permitido.',
                ])
                ->onlyInput('login');
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, (bool) ($data['remember'] ?? false));

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function resolveUser(string $login): ?User
    {
        $login = trim($login);
        $normalizedDigits = preg_replace('/\D+/', '', $login) ?: '';
        $email = Str::lower($login);

        $documentCandidates = [$login];
        $phoneCandidates = [$login];

        if ($normalizedDigits !== '') {
            $documentCandidates[] = $normalizedDigits;
            $phoneCandidates[] = $normalizedDigits;

            if (strlen($normalizedDigits) === 11) {
                $documentCandidates[] = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $normalizedDigits);
                $phoneCandidates[] = preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $normalizedDigits);
            }

            if (strlen($normalizedDigits) === 10) {
                $phoneCandidates[] = preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $normalizedDigits);
            }
        }

        $documentCandidates = array_values(array_unique(array_filter($documentCandidates)));
        $phoneCandidates = array_values(array_unique(array_filter($phoneCandidates)));

        return User::query()
            ->where(function ($query) use ($email, $documentCandidates, $phoneCandidates) {
                $query->whereRaw('lower(email) = ?', [$email]);

                if ($documentCandidates !== []) {
                    $query->orWhereIn('document', $documentCandidates);
                }

                if ($phoneCandidates !== []) {
                    $query->orWhereIn('phone', $phoneCandidates);
                }
            })
            ->first();
    }

    private function throttleKey(Request $request, string $login): string
    {
        return Str::transliterate(Str::lower(trim($login))).'|'.$request->ip();
    }
}
