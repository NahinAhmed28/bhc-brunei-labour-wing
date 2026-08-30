<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $ok = Auth::attempt($credentials, $request->boolean('remember'));
        LoginAttempt::create(['email' => $credentials['email'], 'successful' => $ok, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        if (! $ok) {
            throw ValidationException::withMessages(['email' => 'The supplied credentials are invalid.']);
        }if (! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'This account is inactive.']);
        }$request->session()->regenerate();
        Auth::user()->update(['last_login_at' => now()]);
        AuditService::record('login', 'authentication', Auth::id());

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        AuditService::record('logout', 'authentication', Auth::id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
