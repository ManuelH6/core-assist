<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin($request);
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Veuillez saisir votre adresse e-mail.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'password.required' => 'Veuillez saisir votre mot de passe.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'Identifiants incorrects. Vérifiez votre e-mail et votre mot de passe.',
                ]);
        }

        $request->session()->regenerate();

        return $this->redirectAfterLogin($request);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Vous avez été déconnecté.');
    }

    private function redirectAfterLogin(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->isClient()) {
            return redirect()->intended(route('client-portal.index'));
        }

        if ($user && $user->canManageClientsAndRequests()) {
            return redirect()->intended(route('requests.index'));
        }

        return redirect()->intended(route('missions.index'));
    }
}
