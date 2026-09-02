<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_item', function (Blueprint $table) {
            // Opsional sesuai PRD §5.4: fallback ke master_barang.spesifikasi_default,
            // atau nama_barang, kalau kosong saat generate surat. Migration ini sempat
            // hilang 2x sebelum ke-push, nyebabin error "Column 'spesifikasi' cannot
            // be null" pas simpan transaksi hasil upload Excel dengan Spesifikasi kosong.
            $table->string('spesifikasi')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_item', function (Blueprint $table) {
            $table->string('spesifikasi')->nullable(false)->change();
        });
    }
};