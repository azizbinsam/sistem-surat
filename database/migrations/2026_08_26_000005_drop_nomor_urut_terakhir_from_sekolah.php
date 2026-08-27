<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            // Sudah dibackfill ke tahun_anggaran.nomor_urut_terakhir di migration sebelumnya
            // (lihat 2026_08_26_000003_backfill_tahun_anggaran.php) — sekarang reset per
            // tahun anggaran, bukan seumur hidup sekolah (PRD §12.3).
            $table->dropColumn('nomor_urut_terakhir');
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->unsignedInteger('nomor_urut_terakhir')->default(0);
        });
    }
};
