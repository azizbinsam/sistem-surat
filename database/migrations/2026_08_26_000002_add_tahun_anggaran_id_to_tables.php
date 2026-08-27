<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tabel = ['master_barang', 'pegawai', 'barang_masuk', 'transaksi', 'koreksi_stok'];

    public function up(): void
    {
        // Nullable dulu -> dibackfill di migration berikutnya -> baru di-NOT NULL-kan
        // (biar data existing di database production/lokal nggak keputus pas migrate).
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->foreignId('tahun_anggaran_id')->nullable()->after('sekolah_id')->constrained('tahun_anggaran')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tahun_anggaran_id');
            });
        }
    }
};
