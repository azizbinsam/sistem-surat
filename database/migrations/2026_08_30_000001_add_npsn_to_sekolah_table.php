<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            // NPSN (Nomor Pokok Sekolah Nasional) adalah identitas resmi yang benar-benar
            // unik per sekolah — beda sekolah pasti beda NPSN. Ini pengganti kode_sekolah
            // yang sebelumnya (keliru) dipakai sebagai representasi NPSN (lihat migration
            // 2026_08_27_000001_add_unique_to_kode_sekolah.php), padahal kode_sekolah cuma
            // singkatan buat nomor surat dan banyak sekolah bisa punya kode yang sama.
            // Nullable dulu karena data lama belum punya NPSN asli buat di-backfill.
            $table->string('npsn', 20)->nullable()->unique()->after('kode_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropColumn('npsn');
        });
    }
};
