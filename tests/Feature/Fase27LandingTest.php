<?php

namespace Tests\Feature;

use App\Models\PaketLangganan;
use App\Models\RekeningDonasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 27 — Landing page v3: harga dinamis dari katalog, promo sekolah lama, cara berlangganan, FAQ,
 * dan hilangnya klaim lama (gratis selamanya / donasi / Word & PDF).
 */
class Fase27LandingTest extends TestCase
{
    use RefreshDatabase;

    // ===== Harga dari katalog =====

    public function test_harga_katalog_tampil_di_landing(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="harga"', false)
            ->assertSee('Rp39.000')
            ->assertSee('Rp129.000')
            ->assertSee('Hemat')
            ->assertSee('Rp27.000')   // 4 × 39.000 − 129.000
            ->assertSee('Rp32.250');  // 129.000 ÷ 4
    }

    public function test_mengubah_harga_di_katalog_langsung_tampil_tanpa_deploy(): void
    {
        PaketLangganan::where('kode', 'triwulan')->update(['harga' => 45000]);
        PaketLangganan::where('kode', 'tahunan')->update(['harga' => 150000]);

        $this->get('/')
            ->assertSee('Rp45.000')
            ->assertSee('Rp150.000')
            ->assertSee('Rp30.000')   // 4 × 45.000 − 150.000
            ->assertDontSee('Rp39.000')
            ->assertDontSee('Rp129.000');
    }

    public function test_label_hemat_disembunyikan_kalau_tahunan_tidak_lebih_murah(): void
    {
        PaketLangganan::where('kode', 'tahunan')->update(['harga' => 156000]); // sama dengan 4 × 39.000

        $this->get('/')->assertOk()->assertDontSee('Hemat');
    }

    public function test_bagian_harga_disembunyikan_tanpa_error_kalau_katalog_kosong(): void
    {
        PaketLangganan::query()->delete();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="harga"', false)
            ->assertDontSee('id="cara-berlangganan"', false)
            ->assertSee('id="faq"', false);
    }

    // ===== Jendela gratis (config langganan.gratis) =====

    public function test_info_gratis_tampil_dengan_rentang_dari_konfigurasi(): void
    {
        config(['langganan.gratis' => ['tahun' => 2026, 'sampai_triwulan' => 3, 'tampil_di_landing' => true]]);

        $this->get('/')
            ->assertSee('Gratis untuk Triwulan 1–3 Tahun Anggaran 2026')
            ->assertSee('Laporan untuk periode itu bisa dibuat')
            ->assertSee('Triwulan 4 Tahun Anggaran 2026') // mulai berbayar
            ->assertSee('Apa saja yang gratis?');
    }

    public function test_batas_gratis_mengikuti_konfigurasi(): void
    {
        config(['langganan.gratis' => ['tahun' => 2027, 'sampai_triwulan' => 4, 'tampil_di_landing' => true]]);

        $this->get('/')
            ->assertSee('Triwulan 1–4 Tahun Anggaran 2027')
            ->assertSee('Triwulan 1 Tahun Anggaran 2028'); // jendela menutup TW4 → berbayar mulai TW1 tahun berikutnya
    }

    public function test_info_gratis_hilang_kalau_dimatikan_atau_tahun_nol(): void
    {
        config(['langganan.gratis' => ['tahun' => 2026, 'sampai_triwulan' => 3, 'tampil_di_landing' => false]]);
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Apa saja yang gratis?')
            ->assertSee('Coba gratis: 3 generate surat tanpa biaya');

        config(['langganan.gratis' => ['tahun' => 0, 'sampai_triwulan' => 3, 'tampil_di_landing' => true]]);
        $this->get('/')->assertDontSee('Apa saja yang gratis?');
    }

    // ===== Cara berlangganan & FAQ =====

    public function test_cara_berlangganan_empat_langkah(): void
    {
        $this->get('/')
            ->assertSee('id="cara-berlangganan"', false)
            ->assertSee('Cara berlangganan')
            ->assertSee('Input data seperti biasa')
            ->assertSee('Pilih triwulan')
            ->assertSee('Transfer & upload bukti')
            ->assertSee('Aktif & kwitansi');
    }

    public function test_faq_memuat_pertanyaan_kunci(): void
    {
        $this->get('/')
            ->assertSee('id="faq"', false)
            ->assertSee('Apa yang berbayar, apa yang gratis?')
            ->assertSee('Kalau saya tidak berlangganan, apakah data saya hilang?')
            ->assertSee('Triwulan mana yang dihitung?')
            ->assertSee('Bagaimana cara membayar dan kapan aktif?')
            ->assertSee('Apakah ada kwitansi?')
            ->assertSee('<details', false);
    }

    public function test_navbar_punya_tautan_ke_bagian_baru(): void
    {
        $this->get('/')
            ->assertSee('href="#fitur"', false)
            ->assertSee('href="#harga"', false)
            ->assertSee('href="#faq"', false);
    }

    // ===== Klaim lama sudah hilang =====

    public function test_klaim_lama_tidak_ada_lagi(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('100% Gratis')
            ->assertDontSee('Tanpa biaya berlangganan')
            ->assertDontSee('Gratis, Didukung Donasi')
            ->assertDontSee('Dukung Kami')
            ->assertDontSee('Word & PDF')
            ->assertDontSee('Kab. Lebak')
            ->assertDontSee('Video Tutorial'); // bagian ini hanya tampil kalau admin sudah mengisi video
    }

    public function test_surat_hanya_word_dan_ada_penjelasan_harga_per_triwulan(): void
    {
        $this->get('/')
            ->assertSee('dalam format Word')
            ->assertSee('Harga Terjangkau, per Triwulan');
    }

    public function test_nomor_rekening_tidak_ditampilkan_publik(): void
    {
        RekeningDonasi::create(['nama_bank' => 'BCA', 'nomor_rekening' => '9998887776', 'atas_nama' => 'Delix Studio', 'urutan' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('9998887776')
            ->assertSee('Nomor rekening tujuan ditampilkan di halaman invoice');
    }

    // ===== Tombol & banner =====

    public function test_tombol_daftar_mengarah_ke_register(): void
    {
        $this->get('/')->assertSee('href="' . route('register') . '"', false)->assertSee('Mulai');
    }

    public function test_slot_banner_masih_berfungsi(): void
    {
        \App\Models\Banner::create([
            'judul' => 'Promo', 'gambar_desktop' => 'banners/d.jpg', 'gambar_mobile' => 'banners/m.jpg',
            'teks_alt' => 'Banner di Landing v3', 'lokasi' => 'landing', 'urutan' => 0, 'aktif' => true,
        ]);

        $this->get('/')->assertSee('Banner di Landing v3');
    }
}
