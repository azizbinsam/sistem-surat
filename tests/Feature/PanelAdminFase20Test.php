<?php

namespace Tests\Feature;

use App\Filament\Pages\KelolaTahunAnggaran;
use App\Filament\Pages\PengaturanAplikasi;
use App\Models\AppSettings;
use App\Models\RekeningDonasi;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelAdminFase20Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // ===== Tahun Anggaran =====

    public function test_buka_tahun_anggaran_baru_bikin_baris_baru_untuk_semua_sekolah(): void
    {
        $sekolahA = Sekolah::create([
            'nama_sekolah' => 'SDN A', 'kode_sekolah' => 'SDNA',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $sekolahB = Sekolah::create([
            'nama_sekolah' => 'SDN B', 'kode_sekolah' => 'SDNB',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);

        // Sekolah::booted() otomatis bikin tahun anggaran 2026 (is_aktif) buat keduanya
        $ta2026A = $sekolahA->tahunAnggaran()->first();
        $ta2026B = $sekolahB->tahunAnggaran()->first();

        Livewire::actingAs($this->admin)
            ->test(KelolaTahunAnggaran::class)
            ->callAction('bukaTahunAnggaranBaru');

        // Kedua sekolah dapat tahun anggaran baru (2027), yang lama jadi nggak aktif
        $this->assertSame(2027, $sekolahA->tahunAnggaran()->where('is_aktif', true)->first()->tahun);
        $this->assertSame(2027, $sekolahB->tahunAnggaran()->where('is_aktif', true)->first()->tahun);
        $this->assertFalse($ta2026A->fresh()->is_aktif);
        $this->assertFalse($ta2026B->fresh()->is_aktif);

        // Tahun anggaran lama TETAP ADA (nggak dihapus)
        $this->assertDatabaseHas('tahun_anggaran', ['id' => $ta2026A->id, 'tahun' => 2026]);
    }

    public function test_tahun_anggaran_baru_mulai_dari_nol(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN A', 'kode_sekolah' => 'SDNA',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $sekolah->tahunAnggaran()->first()->update(['nomor_urut_terakhir' => 50]);

        Livewire::actingAs($this->admin)
            ->test(KelolaTahunAnggaran::class)
            ->callAction('bukaTahunAnggaranBaru');

        $taBaru = $sekolah->tahunAnggaran()->where('is_aktif', true)->first();
        $this->assertSame(0, $taBaru->nomor_urut_terakhir);
    }

    public function test_klik_dua_kali_tidak_bikin_duplikat_tahun_anggaran(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN A', 'kode_sekolah' => 'SDNA',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);

        $component = Livewire::actingAs($this->admin)->test(KelolaTahunAnggaran::class);
        $component->callAction('bukaTahunAnggaranBaru'); // -> 2027
        $component->instance()->bukaTahunAnggaranBaru(2027); // manggil lagi manual dengan tahun sama

        $this->assertSame(1, $sekolah->tahunAnggaran()->where('tahun', 2027)->count());
    }

    // ===== Pengaturan Aplikasi =====

    public function test_admin_bisa_ubah_nama_aplikasi(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PengaturanAplikasi::class)
            ->fillForm(['nama_aplikasi' => 'SuratKu'])
            ->call('simpan')
            ->assertHasNoFormErrors();

        $this->assertSame('SuratKu', AppSettings::current()->nama_aplikasi);
    }

    public function test_nama_aplikasi_wajib_diisi(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PengaturanAplikasi::class)
            ->fillForm(['nama_aplikasi' => ''])
            ->call('simpan')
            ->assertHasFormErrors(['nama_aplikasi' => 'required']);
    }

    public function test_nama_aplikasi_baru_langsung_kepakai_di_title_landing_page(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PengaturanAplikasi::class)
            ->fillForm(['nama_aplikasi' => 'SuratKu'])
            ->call('simpan');

        $response = $this->get('/');
        $response->assertSee('SuratKu');
    }

    // ===== Rekening Donasi =====

    public function test_admin_bisa_tambah_rekening_donasi(): void
    {
        RekeningDonasi::create([
            'nama_bank' => 'BCA',
            'nomor_rekening' => '1234567890',
            'atas_nama' => 'Delix Studio',
            'urutan' => 1,
        ]);

        $this->assertDatabaseHas('rekening_donasi', ['nama_bank' => 'BCA', 'atas_nama' => 'Delix Studio']);
    }

    public function test_dashboard_menampilkan_rekening_donasi_yang_sudah_diinput_admin(): void
    {
        RekeningDonasi::create([
            'nama_bank' => 'BCA', 'nomor_rekening' => '1234567890', 'atas_nama' => 'Delix Studio', 'urutan' => 1,
        ]);

        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN A', 'kode_sekolah' => 'SDNA',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Dukung Kami')
            ->assertSee('BCA')
            ->assertSee('1234567890');
    }
}