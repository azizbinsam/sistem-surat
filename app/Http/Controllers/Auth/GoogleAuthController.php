<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            // stateless() biar nggak gampang gagal gara-gara masalah session
            // cookie di shared hosting (beda subdomain/proxy dsb).
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (InvalidStateException|\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Login pakai Google gagal, coba lagi ya.',
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // Belum pernah login Google, tapi mungkin sudah pernah daftar manual
            // pakai email yang sama -> tautkan akunnya, jangan bikin duplikat.
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Pengguna Google',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(40)), // nggak bakal kepakai, login selalu lewat Google
                    'email_verified_at' => now(), // Google udah verifikasi kepemilikan emailnya
                ]);

                // Bukan email verifikasi (nggak relevan, udah verified dari atas) —
                // ini notifikasi "akun berhasil dibuat" versi Google, tanpa tombol
                // verifikasi sama sekali.
                $user->notify(new \App\Notifications\SelamatDatangGoogleNotification());
            }
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}