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
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('nama');
            $table->string('nip')->nullable();
            $table->string('jabatan');
            $table->enum('kategori', ['kepala_sekolah', 'pengurus_barang_pembantu', 'guru', 'tendik']);
            $table->string('ttd_path')->nullable();
            $table->softDeletes(); // biar surat lama tetap valid kalau pegawai dihapus/pindah
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
