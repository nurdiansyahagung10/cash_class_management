<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_no' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($validated['student_no']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $message = "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.";

            return redirect()->route('login')
                ->withInput($request->only('student_no'))
                ->withErrors(['student_no' => $message])
                ->with('error', $message);
        }

        if (!Auth::attempt([
            'student_no' => $validated['student_no'],
            'password' => $validated['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            $message = 'No. absen / NIM atau password salah.';

            return redirect()->route('login')
                ->withInput($request->only('student_no'))
                ->withErrors(['student_no' => $message])
                ->with('error', $message);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
