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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('nomor_referensi_asal'); // dari kolom excel, buat traceability

            $table->string('nomor_npb')->nullable();
            $table->string('nomor_spb')->nullable();
            $table->string('nomor_sppb')->nullable();

            $table->date('tanggal_npb');
            $table->date('tanggal_spb')->nullable();
            $table->date('tanggal_sppb')->nullable();

            $table->foreignId('pihak_peminta_id')->nullable()->constrained('pegawai');
            $table->enum('status', ['draft', 'siap_generate', 'selesai'])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
