<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cegah 1 akun Google ke-link ke lebih dari 1 user. Nullable-unique,
            // jadi banyak user yang google_id-nya masih null (belum pakai Google
            // Auth) tetap aman, nggak saling bentrok.
            $table->unique('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
        });
    }
};