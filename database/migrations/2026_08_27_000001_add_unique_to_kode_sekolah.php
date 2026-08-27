<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            // Proteksi "1 sekolah 1 akun" (PRD §12.2) — kode_sekolah dipakai sebagai
            // representasi NPSN. Pastikan tidak ada data duplikat dulu sebelum migrate,
            // kalau tidak migration ini akan gagal.
            $table->unique('kode_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropUnique(['kode_sekolah']);
        });
    }
};
