<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aplikasi')->default('Sistem Surat');
            $table->string('logo_aplikasi')->nullable();
            $table->timestamps();
        });

        Schema::create('rekening_donasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bank');
            $table->string('nomor_rekening');
            $table->string('atas_nama');
            $table->string('foto')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        // app_settings adalah singleton (cuma 1 baris) -- isi baris default-nya di sini
        // biar Filament langsung punya record buat diedit, bukan bikin "create" page segala.
        \Illuminate\Support\Facades\DB::table('app_settings')->insert([
            'nama_aplikasi' => 'Sistem Surat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rekening_donasi');
        Schema::dropIfExists('app_settings');
    }
};