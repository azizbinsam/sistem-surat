<?php

namespace Tests\Feature;

use App\Models\BarangMasuk;
use App\Models\BarangMasukItem;
use App\Models\KoreksiStok;
use App\Models\MasterBarang;
use App\Models\Sekolah;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Models\User;
use App\Services\PersediaanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersediaanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PersediaanService $service;
    protected Sekolah $sekolah;
    protected MasterBarang $barang;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PersediaanService::class);

        $this->sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur',
            'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'PEMERINTAH KABUPATEN LEBAK',
            'nama_dinas' => 'DINAS PENDIDIKAN',
            'alamat' => 'Jl. Contoh No. 1',
            'tempat' => 'Rangkasbitung',
        ]);

        $this->barang = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'ATK-001',
            'nama_barang' => 'Kertas HVS',
            'satuan_default' => 'Rim',
        ]);

        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
    }

    /** Helper: bikin barang masuk + 1 item, created_at bisa dipaksa ke waktu tertentu. */
    protected function buatBarangMasuk(string $tanggal, int $jumlah, ?string $createdAt = null): BarangMasukItem
    {
        $masuk = BarangMasuk::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_bpu' => 'BPU-' . uniqid(),
            'tanggal' => $tanggal,
        ]);

        if ($createdAt) {
            $masuk->forceFill(['created_at' => $createdAt])->save();
        }

        return BarangMasukItem::create([
            'barang_masuk_id' => $masuk->id,
            'master_barang_id' => $this->barang->id,
            'spesifikasi' => 'Standar',
            'satuan' => 'Rim',
            'jumlah' => $jumlah,
        ]);
    }

    /** Helper: bikin transaksi keluar + 1 item, created_at bisa dipaksa. */
    protected function buatTransaksi(string $tanggalNpb, int $jumlah, ?string $createdAt = null): Transaksi
    {
        $transaksi = Transaksi::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_referensi_asal' => 'REF-' . uniqid(),
            'tanggal_npb' => $tanggalNpb,
            'status' => 'draft',
        ]);

        if ($createdAt) {
            $transaksi->forceFill(['created_at' => $createdAt])->save();
        }

        TransaksiItem::create([
            'transaksi_id' => $transaksi->id,
            'master_barang_id' => $this->barang->id,
            'spesifikasi' => 'Standar',
            'jumlah' => $jumlah,
            'satuan' => 'Rim',
            'keperluan' => 'Kebutuhan kelas',
        ]);

        return $transaksi;
    }

    /**
     * Ini test untuk bug utama yang dicatat di ARCHITECTURE.md §4:
     * versi lama totalMasuk() tidak difilter tanggal, jadi barang masuk yang
     * dicatat SETELAH sebuah transaksi lama ikut kehitung di "sisa sebelum transaksi" itu.
     */
    public function test_sisa_sebelum_transaksi_mengabaikan_barang_masuk_yang_dicatat_belakangan(): void
    {
        // Barang masuk 10 rim di bulan Januari (sebelum transaksi)
        $this->buatBarangMasuk('2026-01-05', 10);

        // Transaksi keluar terjadi di bulan Februari, minta 4 rim
        $transaksi = $this->buatTransaksi('2026-02-01', 4);

        // Sekolah baru sadar telat generate suratnya, dan di antara waktu itu
        // ada barang masuk susulan 20 rim di bulan Maret (SETELAH transaksi Februari)
        $this->buatBarangMasuk('2026-03-10', 20);

        // Sisa persediaan yang dicetak di surat SPB untuk transaksi Februari
        // harus tetap 10 (stok Januari), BUKAN 30 (10 + 20 barang masuk susulan Maret)
        $sisa = $this->service->sisaSebelumTransaksi($this->barang->id, $transaksi);

        $this->assertSame(10, $sisa);
    }

    public function test_sisa_sebelum_transaksi_mengabaikan_koreksi_stok_yang_dicatat_belakangan(): void
    {
        $this->buatBarangMasuk('2026-01-05', 10);
        $transaksi = $this->buatTransaksi('2026-02-01', 4);

        // Koreksi stok (misal hasil opname) dicatat belakangan di bulan Maret
        KoreksiStok::create([
            'sekolah_id' => $this->sekolah->id,
            'master_barang_id' => $this->barang->id,
            'tanggal' => '2026-03-10',
            'jumlah' => -3,
            'alasan' => 'Rusak kena air',
            'user_id' => $this->user->id,
        ]);

        $sisa = $this->service->sisaSebelumTransaksi($this->barang->id, $transaksi);

        $this->assertSame(10, $sisa);
    }

    /**
     * Tie-breaker untuk tanggal yang sama harus pakai created_at, bukan id —
     * karena urutan id antar tabel berbeda tidak selalu mencerminkan urutan waktu asli.
     */
    public function test_cutoff_pakai_created_at_sebagai_tie_breaker_untuk_tanggal_sama(): void
    {
        // Transaksi dibuat (created_at) LEBIH DULU...
        $transaksi = $this->buatTransaksi('2026-02-01', 4, createdAt: '2026-02-01 08:00:00');

        // ...tapi barang masuk dengan id LEBIH BESAR, di tanggal yang SAMA,
        // baru benar-benar dientry belakangan pada hari itu (created_at lebih telat)
        $this->buatBarangMasuk('2026-02-01', 15, createdAt: '2026-02-01 09:00:00');

        // Karena created_at barang masuk itu SETELAH created_at transaksi,
        // barang masuk ini seharusnya TIDAK ikut terhitung di "sisa sebelum transaksi"
        $sisa = $this->service->sisaSebelumTransaksi($this->barang->id, $transaksi);

        $this->assertSame(0, $sisa);
    }

    public function test_sisa_saat_ini_menghitung_semua_data_sampai_hari_ini(): void
    {
        $this->buatBarangMasuk(now()->subDays(5)->toDateString(), 10);
        $this->buatTransaksi(now()->subDay()->toDateString(), 3);

        $sisa = $this->service->sisaSaatIni($this->barang->id);

        $this->assertSame(7, $sisa);
    }

    public function test_total_keluar_mengecualikan_transaksi_itu_sendiri(): void
    {
        $this->buatBarangMasuk('2026-01-01', 20);

        // Dua transaksi di tanggal yang sama
        $transaksiA = $this->buatTransaksi('2026-02-01', 5, createdAt: '2026-02-01 08:00:00');
        $this->buatTransaksi('2026-02-01', 5, createdAt: '2026-02-01 08:00:00');

        // Sisa sebelum transaksi A tidak boleh ikut mengurangi jumlah transaksi A sendiri,
        // meskipun created_at-nya identik dengan transaksi lain di tanggal yang sama
        $sisa = $this->service->sisaSebelumTransaksi($this->barang->id, $transaksiA);

        $this->assertSame(20, $sisa);
    }

    /**
     * Regresi: bug yang dilaporin user — dua transaksi dari upload Excel yang sama
     * (satu file, diproses berturut-turut dalam 1 request) sering punya created_at
     * IDENTIK sampai ke detik (presisi default MySQL). Dulu, karena tie-breaker-nya
     * cuma created_at, transaksi kedua nggak nganggep transaksi pertama "udah
     * kejadian duluan" — jadi "sisa sebelum transaksi" keduanya sama persis,
     * padahal harusnya transaksi kedua sisanya udah kepotong sama transaksi pertama.
     *
     * Fix: totalKeluar() (Transaksi vs Transaksi, tabel yang SAMA) sekarang pakai
     * id sebagai tie-breaker kalau created_at identik — id auto-increment selalu
     * urut sesuai urutan insert, nggak kayak created_at yang presisinya cuma detik.
     */
    public function test_dua_transaksi_dari_upload_bulk_created_at_identik_sisa_tetap_progresif(): void
    {
        $this->buatBarangMasuk('2026-01-01', 45);

        // Simulasikan upload Excel: 2 transaksi, tanggal_npb SAMA, created_at
        // IDENTIK persis (kejadian umum kalau diproses dalam request/detik yang sama)
        $transaksiPertama = $this->buatTransaksi('2026-02-01', 8, createdAt: '2026-02-01 10:00:00');
        $transaksiKedua = $this->buatTransaksi('2026-02-01', 3, createdAt: '2026-02-01 10:00:00');

        $sisaSebelumPertama = $this->service->sisaSebelumTransaksi($this->barang->id, $transaksiPertama);
        $sisaSebelumKedua = $this->service->sisaSebelumTransaksi($this->barang->id, $transaksiKedua);

        // Transaksi pertama: belum ada yang keluar sebelumnya -> sisa penuh 45
        $this->assertSame(45, $sisaSebelumPertama);

        // Transaksi kedua: HARUS udah kepotong 8 dari transaksi pertama (id-nya lebih
        // kecil, walau created_at identik) -> sisa 37, BUKAN 45 lagi
        $this->assertSame(37, $sisaSebelumKedua);
    }
}
