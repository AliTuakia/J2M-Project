<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('login', ['register' => false]);
    }

    public function registerForm()
    {
        return view('login', ['register' => true]);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:255']);
        $key = Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Silakan coba kembali satu menit lagi.']);
        }
        if (! Auth::attempt($data)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email atau password tidak sesuai.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended($request->user()->role === 'admin' ? route('admin.dashboard') : route('catalog'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', 'max:255', Password::min(8)],
        ]);
        Auth::login(User::create($data));
        $request->session()->regenerate();

        return redirect()->intended(route('catalog'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
