<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    // 1) Lempar user ke halaman login Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // 2) Google callback ke sini setelah user pilih akun
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Login Google gagal. Silakan coba lagi.']);
        }

        $email = $googleUser->getEmail();

        // kalau google tidak ngasih email (jarang banget, tapi jaga-jaga)
        if (!$email) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Email dari Google tidak ditemukan.']);
        }

        // MODEL 1: cari user berdasarkan email yang sudah ada di database
        $user = User::where('email', $email)->first();

        // kalau user tidak ada → TOLAK (tidak auto-register)
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Email Google ini belum terdaftar. Hubungi guru/admin.']);
        }

        // login-kan user
        Auth::login($user, true);

        return redirect()->route('dashboard');
    }
}
