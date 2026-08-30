<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_anggaran', function (Blueprint $table) {
            $table->enum('status', ['hold', 'aktif'])->default('hold')->after('nomor_urut_terakhir');
        });

        // Backfill dari is_aktif lama sebelum kolomnya dihapus.
        DB::table('tahun_anggaran')->where('is_aktif', true)->update(['status' => 'aktif']);
        DB::table('tahun_anggaran')->where('is_aktif', false)->update(['status' => 'hold']);

        Schema::table('tahun_anggaran', function (Blueprint $table) {
            $table->dropColumn('is_aktif');
        });
    }

    public function down(): void
    {
        Schema::table('tahun_anggaran', function (Blueprint $table) {
            $table->boolean('is_aktif')->default(false)->after('nomor_urut_terakhir');
        });

        DB::table('tahun_anggaran')->where('status', 'aktif')->update(['is_aktif' => true]);

        Schema::table('tahun_anggaran', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};