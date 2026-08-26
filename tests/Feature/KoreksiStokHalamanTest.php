<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class KoreksiStokHalamanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_koreksi_stok_render_dan_arahkan_ke_edit_bpu_transaksi(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur',
            'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'PEMKAB LEBAK',
            'nama_dinas' => 'DISDIK',
            'alamat' => 'Jl. Contoh',
            'tempat' => 'Rangkasbitung',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        Volt::actingAs($user)
            ->test('pages.persediaan.koreksi')
            ->assertOk()
            ->assertSee('kejadian fisik nyata')
            ->assertSee('jangan pakai Koreksi')
            ->assertSeeHtml(route('barang-masuk.index'))
            ->assertSeeHtml(route('transaksi.index'));
    }
}
