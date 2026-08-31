<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome', [
        // PaketSubscription (model harga lama) sudah nggak dipakai di landing page sejak
        // pivot v1.1 ke gratis + donasi (Fase 22). Modelnya tetap ada karena masih dipakai
        // Subscription & Filament resource, cuma sudah nggak relevan buat ditampilkan publik.
        'rekeningDonasi' => \Illuminate\Support\Facades\Schema::hasTable('rekening_donasi')
            ? \App\Models\RekeningDonasi::orderBy('urutan')->get()
            : collect(),
    ]);
})->name('welcome');

Route::middleware(['auth'])->group(function () {
    // TIDAK dikasih middleware('verified') dengan sengaja — sekolah harus tetap
    // bisa login & pakai dashboard walau emailnya belum diverifikasi. Status
    // verifikasi cuma ditampilkan sebagai banner non-blocking (lihat
    // layout.verifikasi-email-banner), verifikasi bisa dilakukan sambil jalan.
    Volt::route('dashboard', 'pages.dashboard')
        ->name('dashboard');

    Volt::route('lengkapi-profil', 'pages.onboarding.lengkapi-profil')
        ->name('lengkapi-profil');

    Route::prefix('master-barang')->name('master-barang.')->group(function () {
        Volt::route('/', 'pages.master-barang.index')->name('index');
        Volt::route('create', 'pages.master-barang.create')->name('create');
        Volt::route('{masterBarang}/edit', 'pages.master-barang.edit')->name('edit');
        Volt::route('import', 'pages.master-barang.import')->name('import');
        Route::get('template-download', function () {
            return response()->download(storage_path('app/templates/template_master_barang.xlsx'), 'template_master_barang.xlsx');
        })->name('template');
    });

    Route::prefix('pegawai')->name('pegawai.')->group(function () {
        Volt::route('/', 'pages.pegawai.index')->name('index');
        Volt::route('create', 'pages.pegawai.create')->name('create');
        Volt::route('{pegawai}/edit', 'pages.pegawai.edit')->name('edit');
        Volt::route('import', 'pages.pegawai.import')->name('import');
        Route::get('template-download', function () {
            return response()->download(storage_path('app/templates/template_pegawai.xlsx'), 'template_pegawai.xlsx');
        })->name('template');
    });

    Route::prefix('barang-masuk')->name('barang-masuk.')->group(function () {
        Volt::route('/', 'pages.barang-masuk.index')->name('index');
        Volt::route('create', 'pages.barang-masuk.create')->name('create');
        Volt::route('{barangMasuk}/edit', 'pages.barang-masuk.edit')->name('edit');
        Volt::route('upload', 'pages.barang-masuk.upload')->name('upload');

        Route::get('template-download', function () {
            return response()->download(storage_path('app/templates/template_penerimaan_barang.xlsx'), 'template_penerimaan_barang.xlsx');
        })->name('template');
    });

    Route::prefix('transaksi')->name('transaksi.')->group(function () {
        Volt::route('/', 'pages.transaksi.index')->name('index');
        Volt::route('create', 'pages.transaksi.create')->name('create');
        Volt::route('{transaksi}/edit', 'pages.transaksi.edit')->name('edit');
        Volt::route('upload', 'pages.transaksi.upload')->name('upload');

        Route::get('template-download', function () {
            return response()->download(storage_path('app/templates/template_transaksi_keluar.xlsx'), 'template_transaksi_keluar.xlsx');
        })->name('template');
    });

    Route::prefix('persediaan')->name('persediaan.')->group(function () {
        Volt::route('/', 'pages.persediaan.index')->name('index');
        Volt::route('{masterBarang}/riwayat', 'pages.persediaan.riwayat')->name('riwayat');
        Volt::route('koreksi', 'pages.persediaan.koreksi')->name('koreksi');
    });

    Volt::route('pengaturan-sekolah', 'pages.pengaturan.sekolah')->name('pengaturan.sekolah');
});


// Preview template email di browser TANPA benar-benar ngirim — cuma aktif di
// local & testing, sengaja nggak dibuka di production (nggak butuh auth,
// jadi kalau kebuka di production siapa aja bisa akses & itu bukan hal yang
// perlu publik). Buka: /dev/preview-email/verifikasi atau /reset-password.
if (app()->environment(['local', 'testing'])) {
    Route::prefix('dev/preview-email')->group(function () {
        Route::get('verifikasi', function () {
            $user = new \App\Models\User(['name' => 'Budi Santoso', 'email' => 'budi@contohsekolah.sch.id']);
            $url = 'https://app.namadomain.com/verify-email/1/contohhash1234567890?expires=9999999999&signature=contohsignature1234567890';

            return new \App\Mail\VerifikasiEmailMail($user, $url);
        })->name('dev.preview-email.verifikasi');

        Route::get('reset-password', function () {
            $user = new \App\Models\User(['name' => 'Budi Santoso', 'email' => 'budi@contohsekolah.sch.id']);
            $url = 'https://app.namadomain.com/reset-password/contohtoken1234567890?email=budi%40contohsekolah.sch.id';

            return new \App\Mail\ResetPasswordMail($user, $url);
        })->name('dev.preview-email.reset-password');

        Route::get('selamat-datang-google', function () {
            $user = new \App\Models\User(['name' => 'Budi Santoso', 'email' => 'budi@gmail.com']);

            return new \App\Mail\SelamatDatangGoogleMail($user);
        })->name('dev.preview-email.selamat-datang-google');
    });
}

require __DIR__ . '/auth.php';