<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tabel = ['master_barang', 'pegawai', 'barang_masuk', 'transaksi', 'koreksi_stok'];

    public function up(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->foreignId('tahun_anggaran_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->foreignId('tahun_anggaran_id')->nullable()->change();
            });
        }
    }
};
