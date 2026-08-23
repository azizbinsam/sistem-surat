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
     * Run the database seeds.
     */
    public function run(): void
    {
        $sekolah = Sekolah::first();

        User::create([
            'name' => 'Admin Delix Studio',
            'email' => 'admin@delixstudio.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Operator SDN 3 Rangkasbitung Timur',
            'email' => 'sekolah@example.com',
            'password' => Hash::make('password'),
            'role' => 'sekolah',
            'sekolah_id' => $sekolah->id,
        ]);
    }
}
