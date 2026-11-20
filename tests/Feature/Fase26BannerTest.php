<?php

namespace Tests\Feature;

use App\Filament\Resources\BannerResource;
use App\Filament\Resources\BannerResource\Pages\CreateBanner;
use App\Filament\Resources\BannerResource\Pages\ListBanners;
use App\Models\Banner;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Fase 26 — Banner: model & jadwal tayang, komponen slider, tampil di dashboard & landing, dan menu admin.
 */
class Fase26BannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur', 'kode_sekolah' => 'SDN3RKST', 'npsn' => '20601936',
            'nama_pemerintah' => 'PEMKAB LEBAK', 'nama_dinas' => 'DISDIK', 'alamat' => 'Jl. Contoh', 'tempat' => 'Rangkasbitung',
        ]);
        $this->user = User::factory()->create(['sekolah_id' => $sekolah->id]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    protected function banner(array $override = []): Banner
    {
        return Banner::create($override + [
            'judul' => 'Promo',
            'gambar_desktop' => 'banners/d.jpg',
            'gambar_mobile' => 'banners/m.jpg',
            'teks_alt' => 'Teks alt promo',
            'lokasi' => 'keduanya',
            'urutan' => 0,
            'aktif' => true,
        ]);
    }

    // ===== Model: siapa yang tayang =====

    public function test_hanya_banner_aktif_yang_tayang(): void
    {
        $this->banner(['judul' => 'Aktif']);
        $this->banner(['judul' => 'Mati', 'aktif' => false]);

        $this->assertSame(['Aktif'], Banner::tayang('dashboard')->pluck('judul')->all());
    }

    public function test_filter_lokasi_dan_keduanya(): void
    {
        $this->banner(['judul' => 'Dash', 'lokasi' => 'dashboard']);
        $this->banner(['judul' => 'Land', 'lokasi' => 'landing']);
        $this->banner(['judul' => 'Dua', 'lokasi' => 'keduanya']);

        $this->assertEqualsCanonicalizing(['Dash', 'Dua'], Banner::tayang('dashboard')->pluck('judul')->all());
        $this->assertEqualsCanonicalizing(['Land', 'Dua'], Banner::tayang('landing')->pluck('judul')->all());
    }

    public function test_jadwal_tayang_dihormati(): void
    {
        $this->banner(['judul' => 'Belum mulai', 'tayang_mulai' => now()->addDay()]);
        $this->banner(['judul' => 'Sudah berakhir', 'tayang_selesai' => now()->subDay()]);
        $this->banner(['judul' => 'Sedang tayang', 'tayang_mulai' => now()->subDay(), 'tayang_selesai' => now()->addDay()]);
        $this->banner(['judul' => 'Tanpa jadwal']);

        $this->assertEqualsCanonicalizing(
            ['Sedang tayang', 'Tanpa jadwal'],
            Banner::tayang('landing')->pluck('judul')->all()
        );
    }

    public function test_urutan_lalu_id(): void
    {
        $this->banner(['judul' => 'C', 'urutan' => 2]);
        $this->banner(['judul' => 'A', 'urutan' => 0]);
        $this->banner(['judul' => 'B', 'urutan' => 0]);

        $this->assertSame(['A', 'B', 'C'], Banner::tayang('dashboard')->pluck('judul')->all());
    }

    public function test_status_tayang_untuk_tabel_admin(): void
    {
        $this->assertSame('Tayang', $this->banner()->statusTayang());
        $this->assertSame('Nonaktif', $this->banner(['aktif' => false])->statusTayang());
        $this->assertSame('Terjadwal', $this->banner(['tayang_mulai' => now()->addDay()])->statusTayang());
        $this->assertSame('Berakhir', $this->banner(['tayang_selesai' => now()->subDay()])->statusTayang());
    }

    public function test_link_hanya_http_https_atau_path_relatif(): void
    {
        $this->assertSame('https://contoh.id/promo', $this->banner(['link_url' => 'https://contoh.id/promo'])->linkAman());
        $this->assertSame('http://contoh.id', $this->banner(['link_url' => 'http://contoh.id'])->linkAman());
        $this->assertSame('/register', $this->banner(['link_url' => '/register'])->linkAman());

        $this->assertNull($this->banner(['link_url' => 'javascript:alert(1)'])->linkAman());
        $this->assertNull($this->banner(['link_url' => 'data:text/html,<script>alert(1)</script>'])->linkAman());
        $this->assertNull($this->banner(['link_url' => '//jahat.example'])->linkAman());
        $this->assertNull($this->banner(['link_url' => null])->linkAman());
    }

    public function test_menghapus_banner_menghapus_gambarnya(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/d.jpg', 'x');
        Storage::disk('public')->put('banners/m.jpg', 'x');
        $banner = $this->banner();

        $banner->delete();

        Storage::disk('public')->assertMissing('banners/d.jpg');
        Storage::disk('public')->assertMissing('banners/m.jpg');
    }

    public function test_mengganti_gambar_menghapus_file_lama(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/d.jpg', 'x');
        Storage::disk('public')->put('banners/d2.jpg', 'x');
        $banner = $this->banner();

        $banner->update(['gambar_desktop' => 'banners/d2.jpg']);

        Storage::disk('public')->assertMissing('banners/d.jpg');
        Storage::disk('public')->assertExists('banners/d2.jpg');
    }

    // ===== Komponen slider =====

    public function test_komponen_tidak_merender_apa_pun_tanpa_banner(): void
    {
        $this->blade('<x-banner-slider lokasi="dashboard" />')->assertDontSee('data-banner-slider', false);
    }

    public function test_satu_banner_tampil_tanpa_titik_navigasi_dan_skrip(): void
    {
        $this->banner(['teks_alt' => 'Diskon Besar']);

        $this->blade('<x-banner-slider lokasi="dashboard" />')
            ->assertSee('data-banner-slider', false)
            ->assertSee('Diskon Besar', false)
            ->assertDontSee('data-dot', false)
            ->assertDontSee('__bannerSliderInit', false);
    }

    public function test_responsif_gambar_desktop_dan_ponsel(): void
    {
        $this->banner(['gambar_desktop' => 'banners/desk.jpg', 'gambar_mobile' => 'banners/mob.jpg']);

        $this->blade('<x-banner-slider lokasi="landing" />')
            ->assertSee('<source media="(min-width: 768px)" srcset="' . asset('storage/banners/desk.jpg') . '">', false)
            ->assertSee('src="' . asset('storage/banners/mob.jpg') . '"', false);
    }

    public function test_lebih_dari_satu_banner_menjadi_slider_otomatis(): void
    {
        $this->banner(['judul' => 'Satu', 'teks_alt' => 'Alt Satu']);
        $this->banner(['judul' => 'Dua', 'teks_alt' => 'Alt Dua']);

        $this->blade('<x-banner-slider lokasi="dashboard" />')
            ->assertSee('Alt Satu', false)
            ->assertSee('Alt Dua', false)
            ->assertSee('data-dot="0"', false)
            ->assertSee('data-dot="1"', false)
            ->assertSee('aria-label="Banner 2"', false)
            ->assertSee('data-interval="5000"', false)
            ->assertSee('__bannerSliderInit', false);
    }

    public function test_banner_dengan_link_bisa_diklik_dan_link_eksternal_aman(): void
    {
        $this->banner(['link_url' => 'https://contoh.id/promo']);

        $this->blade('<x-banner-slider lokasi="dashboard" />')
            ->assertSee('href="https://contoh.id/promo"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_link_berbahaya_tidak_pernah_dirender(): void
    {
        $this->banner(['link_url' => 'javascript:alert(1)']);

        $this->blade('<x-banner-slider lokasi="dashboard" />')
            ->assertDontSee('javascript:alert', false)
            ->assertSee('data-banner-slider', false);
    }

    public function test_teks_alt_di_escape(): void
    {
        $this->banner(['teks_alt' => '"><script>alert(1)</script>']);

        $this->blade('<x-banner-slider lokasi="dashboard" />')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ===== Tampil di halaman =====

    public function test_dashboard_sekolah_menampilkan_banner_dashboard_bukan_landing(): void
    {
        $this->banner(['teks_alt' => 'Banner Dashboard Saja', 'lokasi' => 'dashboard']);
        $this->banner(['teks_alt' => 'Banner Landing Saja', 'lokasi' => 'landing']);

        Volt::actingAs($this->user)->test('pages.dashboard')
            ->assertSee('Banner Dashboard Saja')
            ->assertDontSee('Banner Landing Saja');
    }

    public function test_landing_page_menampilkan_banner_landing(): void
    {
        $this->banner(['teks_alt' => 'Banner Landing Saja', 'lokasi' => 'landing']);
        $this->banner(['teks_alt' => 'Banner Dashboard Saja', 'lokasi' => 'dashboard']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Banner Landing Saja')
            ->assertDontSee('Banner Dashboard Saja');
    }

    public function test_landing_dan_dashboard_aman_tanpa_banner(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-banner-slider', false);
        Volt::actingAs($this->user)->test('pages.dashboard')->assertSuccessful();
    }

    // ===== Panel admin =====

    public function test_admin_membuat_banner_dengan_gambar_ukuran_tepat(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(CreateBanner::class)
            ->fillForm([
                'judul' => 'Promo Januari',
                'teks_alt' => 'Promo Januari',
                'gambar_desktop' => UploadedFile::fake()->image('desktop.jpg', 1600, 400),
                'gambar_mobile' => UploadedFile::fake()->image('mobile.jpg', 800, 600),
                'lokasi' => 'keduanya',
                'urutan' => 1,
                'aktif' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $banner = Banner::where('judul', 'Promo Januari')->first();
        $this->assertNotNull($banner);
        Storage::disk('public')->assertExists($banner->gambar_desktop);
        Storage::disk('public')->assertExists($banner->gambar_mobile);
        $this->assertStringStartsWith('banners/', $banner->gambar_desktop);
    }

    public function test_admin_ditolak_kalau_ukuran_gambar_tidak_tepat(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(CreateBanner::class)
            ->fillForm([
                'judul' => 'Salah ukuran',
                'teks_alt' => 'Salah ukuran',
                'gambar_desktop' => UploadedFile::fake()->image('desktop.jpg', 1000, 400), // harusnya 1600×400
                'gambar_mobile' => UploadedFile::fake()->image('mobile.jpg', 800, 600),
                'lokasi' => 'keduanya',
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(0, Banner::count());
    }

    public function test_admin_ditolak_untuk_tautan_berbahaya(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(CreateBanner::class)
            ->fillForm([
                'judul' => 'Link jahat',
                'teks_alt' => 'Link jahat',
                'link_url' => 'javascript:alert(1)',
                'gambar_desktop' => UploadedFile::fake()->image('desktop.jpg', 1600, 400),
                'gambar_mobile' => UploadedFile::fake()->image('mobile.jpg', 800, 600),
                'lokasi' => 'keduanya',
            ])
            ->call('create')
            ->assertHasFormErrors(['link_url' => 'regex']);

        $this->assertSame(0, Banner::count());
    }

    public function test_tabel_banner_di_admin_menampilkan_status(): void
    {
        $tayang = $this->banner(['judul' => 'Sedang tayang']);
        $terjadwal = $this->banner(['judul' => 'Nanti', 'tayang_mulai' => now()->addWeek()]);

        Livewire::actingAs($this->admin)
            ->test(ListBanners::class)
            ->assertCanSeeTableRecords([$tayang, $terjadwal])
            ->assertTableColumnStateSet('status_tayang', 'Tayang', $tayang)
            ->assertTableColumnStateSet('status_tayang', 'Terjadwal', $terjadwal);
    }

    public function test_menu_banner_ada_di_kelompok_konten_dan_halaman_bisa_dibuka(): void
    {
        $this->actingAs($this->admin)->get(BannerResource::getUrl())->assertOk();
        $this->assertSame('Konten', BannerResource::getNavigationGroup());
    }

    public function test_user_sekolah_tidak_bisa_membuka_menu_banner(): void
    {
        $this->actingAs($this->user)->get(BannerResource::getUrl())->assertForbidden();
    }
}
