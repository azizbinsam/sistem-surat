<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 24 — Langganan Triwulan (doc/LANGGANAN.md §7).
 *
 * Menggantikan model langganan lama (tabel `subscriptions` + `paket_subscription`, Fersaku):
 *  - paket_langganan : katalog harga (Triwulan & Tahunan), bisa diubah dari panel admin
 *  - invoice / invoice_item : tagihan yang dibuat sekolah
 *  - hak_triwulan : izin generate surat per (tahun anggaran, triwulan)
 *  - sekolah.generate_percobaan_terpakai : hitungan kuota percobaan gratis
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabel lama sudah tidak dipakai (database boleh di-reset, tidak ada data penting).
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('paket_subscription');

        Schema::create('paket_langganan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // 'triwulan' | 'tahunan'
            $table->string('nama');
            $table->unsignedInteger('harga'); // rupiah, tanpa desimal
            $table->unsignedTinyInteger('jumlah_triwulan');
            $table->timestamps();
        });

        // Harga awal dari keputusan: Triwulan Rp39.000, Tahunan Rp129.000. Bisa diubah di panel admin.
        DB::table('paket_langganan')->insert([
            ['kode' => 'triwulan', 'nama' => 'Triwulan', 'harga' => 39000, 'jumlah_triwulan' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'tahunan', 'nama' => 'Tahunan', 'harga' => 129000, 'jumlah_triwulan' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('invoice', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->nullable()->unique(); // diisi setelah baris dibuat: INV-{tahun}-{id}
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('diskon')->default(0);
            $table->unsignedInteger('total');
            $table->unsignedSmallInteger('kode_unik');
            $table->unsignedInteger('total_bayar'); // total + kode_unik
            $table->enum('status', ['menunggu_pembayaran', 'menunggu_verifikasi', 'lunas', 'ditolak', 'kedaluwarsa', 'dibatalkan'])->default('menunggu_pembayaran');
            $table->enum('metode', ['manual', 'ipaymu'])->default('manual');
            $table->string('bukti_transfer')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->dateTime('jatuh_tempo_at');
            $table->dateTime('dibayar_at')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('diverifikasi_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'total_bayar']);
        });

        Schema::create('invoice_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoice')->cascadeOnDelete();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->cascadeOnDelete();
            $table->unsignedTinyInteger('triwulan');
            $table->unsignedInteger('harga');
            $table->timestamps();
        });

        Schema::create('hak_triwulan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->cascadeOnDelete();
            $table->unsignedTinyInteger('triwulan'); // 1–4
            $table->enum('sumber', ['berbayar', 'gratis', 'manual'])->default('berbayar');
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_item')->nullOnDelete();
            $table->date('berlaku_sampai')->nullable(); // kosong = tanpa batas
            $table->enum('status', ['aktif', 'dicabut'])->default('aktif');
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['tahun_anggaran_id', 'triwulan']);
        });

        Schema::table('sekolah', function (Blueprint $table) {
            $table->unsignedTinyInteger('generate_percobaan_terpakai')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropColumn('generate_percobaan_terpakai');
        });

        Schema::dropIfExists('hak_triwulan');
        Schema::dropIfExists('invoice_item');
        Schema::dropIfExists('invoice');
        Schema::dropIfExists('paket_langganan');
    }
};
