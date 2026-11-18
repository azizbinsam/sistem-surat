<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Mencatat waktu login terakhir. Event Login dipicu Laravel setiap kali Auth::login()/attempt() berhasil,
 * termasuk login Google dan login admin Filament.
 *
 * Nama method sengaja `catat` (bukan `handle`) dan didaftarkan manual di AppServiceProvider,
 * supaya tidak terdaftar dua kali oleh auto-discovery listener Laravel.
 */
class CatatLoginTerakhir
{
    public function catat(Login $event): void
    {
        if (!$event->user instanceof User) {
            return;
        }

        // update lewat query (bukan $user->save()) supaya tidak memicu event model lain & tidak mengubah objek user yang sedang dipakai
        User::whereKey($event->user->getAuthIdentifier())->update(['last_login_at' => now()]);
    }
}
