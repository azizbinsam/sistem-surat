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
        Schema::create('sekolah', function (Blueprint $table) {
            $table->id();

            // Identitas umum
            $table->string('nama_sekolah');
            $table->string('kode_sekolah'); // contoh: SDN3RKST
            $table->string('logo_sekolah')->nullable();
            $table->string('logo_kabupaten')->nullable();
            $table->string('kontak_wa')->nullable();
            $table->string('email')->nullable();

            // Kop surat
            $table->string('nama_pemerintah'); // contoh: PEMERINTAH KABUPATEN LEBAK
            $table->string('nama_dinas'); // contoh: DINAS PENDIDIKAN
            $table->string('nama_korwil')->nullable(); // contoh: KORWIL SATUAN PENDIDIKAN
            $table->text('alamat');
            $table->string('tempat'); // contoh: Rangkasbitung

            // Kode klasifikasi surat
            $table->string('kode_klasifikasi_surat')->default('000.2.3.1');

            //jabatan resmi sppb
            $table->string('jabatan_resmi_sppb')->default('Kuasa Pengguna Barang');

            // Kode format surat (pojok kanan atas)
            $table->string('format_kode_npb')->nullable();
            $table->string('format_kode_spb')->nullable();
            $table->string('format_kode_sppb')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sekolah');
    }
};
