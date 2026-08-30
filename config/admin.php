<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Akun Admin
    |--------------------------------------------------------------------------
    |
    | Kredensial akun admin (role=admin, buat akses Filament panel di /admin)
    | diambil dari .env, BUKAN di-hardcode di seeder. Jalankan
    | `php artisan db:seed --class=AdminUserSeeder` kapan aja buat bikin
    | akun admin baru ATAU reset password admin yang lupa — aman dijalankan
    | berkali-kali (idempotent, di-update bukan di-duplikat).
    |
    */

    'name' => env('ADMIN_NAME', 'Admin'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];
