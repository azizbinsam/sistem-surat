<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner', function (Blueprint $table) {
            $table->id();
            $table->string('judul'); // nama internal, tidak tampil ke publik
            $table->string('gambar_desktop'); // 1600 × 400 px, tampil di layar ≥ 768px
            $table->string('gambar_mobile'); // 800 × 600 px, tampil di layar < 768px
            $table->string('teks_alt'); // teks alternatif (aksesibilitas)
            $table->string('link_url', 500)->nullable(); // http(s) atau path relatif diawali "/"
            $table->enum('lokasi', ['dashboard', 'landing', 'keduanya'])->default('keduanya');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->dateTime('tayang_mulai')->nullable();
            $table->dateTime('tayang_selesai')->nullable();
            $table->timestamps();

            $table->index(['aktif', 'lokasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner');
    }
};
