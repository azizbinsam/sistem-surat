<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Bikin (atau reset password) akun admin dari kredensial di .env
     * (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD — lihat config/admin.php).
     *
     * Aman dijalankan berkali-kali: di-updateOrCreate berdasarkan email,
     * jadi kalau akunnya sudah ada, cuma nama & password-nya yang di-sync
     * ulang sesuai .env saat ini — nggak bikin akun duplikat.
     *
     * Jalankan lewat: php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (empty($email) || empty($password)) {
            $this->command->error('ADMIN_EMAIL dan/atau ADMIN_PASSWORD belum diisi di .env. Isi dulu, lalu jalankan ulang seeder ini.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.name'),
                'password' => Hash::make($password),
                'role' => 'admin',
                'sekolah_id' => null,
                'email_verified_at' => now(),
            ],
        );

        $this->command->info("Akun admin siap: {$email}");
    }
}
