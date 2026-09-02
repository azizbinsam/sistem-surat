<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sekolah_id punya foreign key constraint yang selama ini "numpang"
        // ke index unique(sekolah_id, kode_barang) buat kebutuhan index
        // pendukungnya. MySQL/InnoDB nggak ngizinin drop unique index itu
        // kalau foreign key-nya bakal kehilangan index pendukung sama sekali
        // -- makanya bikin index polos di sekolah_id dulu SEBELUM dropUnique.
        Schema::table('master_barang', function (Blueprint $table) {
            $table->index('sekolah_id');
        });

        Schema::table('master_barang', function (Blueprint $table) {
            $table->dropUnique(['sekolah_id', 'kode_barang']);
        });
    }

    public function down(): void
    {
        Schema::table('master_barang', function (Blueprint $table) {
            $table->unique(['sekolah_id', 'kode_barang']);
            $table->dropIndex(['sekolah_id']);
        });
    }
};
