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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('paket_id')->constrained('paket_subscription');
            $table->enum('status', ['aktif', 'hold', 'expired'])->default('aktif');
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');
            $table->string('midtrans_order_id')->nullable();
            $table->foreignId('dibuat_manual_oleh')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
