<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            // kode_sekolah cuma singkatan yang dipakai di format nomor surat (contoh:
            // SDN3RKST) — banyak sekolah lain wajar punya kode yang sama atau mirip.
            // Identitas unik yang benar adalah NPSN (lihat migration
            // 2026_08_30_000001_add_npsn_to_sekolah_table.php), jadi constraint unik di
            // sini dilepas.
            $table->dropUnique(['kode_sekolah']);
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->unique('kode_sekolah');
        });
    }
};
