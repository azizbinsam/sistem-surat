<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Sample user role "sekolah" buat dev/testing lokal — akun admin sudah
     * dipisah dan dikelola AdminUserSeeder (dari .env), bukan di sini lagi.
     */
    public function run(): void
    {
        $sekolah = Sekolah::first();

        User::create([
            'name' => 'Operator SDN 3 Rangkasbitung Timur',
            'email' => 'sekolah@example.com',
            'password' => Hash::make('password'),
            'role' => 'sekolah',
            'sekolah_id' => $sekolah->id,
        ]);
    }
}