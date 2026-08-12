<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $r->validate(['login' => 'required|string', 'password' => 'required']);
        
        $loginType = filter_var($r->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginType => $r->login,
            'password' => $r->password
        ];

        if (Auth::attempt($credentials, $r->boolean('remember'))) {
            if (! Auth::user()->active) {
                Auth::logout();

                return back()->withErrors(['login' => 'This account is inactive.']);
            }$r->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('login');
    }
}
