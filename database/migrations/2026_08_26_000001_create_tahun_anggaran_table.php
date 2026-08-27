<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_urut_terakhir')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            $table->unique(['sekolah_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_anggaran');
    }
};
