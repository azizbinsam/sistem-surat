<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('kode_barang');
            $table->string('nama_barang');
            $table->string('kategori')->nullable();
            $table->string('satuan_default');
            $table->text('keperluan_default')->nullable(); // auto-suggest saat isi excel transaksi
            $table->softDeletes(); // biar data historis surat lama tetap valid kalau barang dihapus
            $table->timestamps();

            $table->unique(['sekolah_id', 'kode_barang']); // kode unik per sekolah, bukan global
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_barang');
    }
};
