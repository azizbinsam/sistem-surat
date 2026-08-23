<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SekolahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sekolah::create([
            'nama_sekolah' => 'SEKOLAH DASAR NEGERI 3 RANGKASBITUNG TIMUR',
            'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'PEMERINTAH KABUPATEN LEBAK',
            'nama_dinas' => 'DINAS PENDIDIKAN',
            'nama_korwil' => 'KORWIL SATUAN PENDIDIKAN',
            'alamat' => 'Kp. Catihan Desa Rangkasbitung Timur Kec. Rangkasbitung, Kab. Lebak, Banten 42313',
            'tempat' => 'Rangkasbitung',
            'kontak_wa' => '081234567890',
            'email' => 'sdn3rangkasbitungtimur@example.com',
            'kode_klasifikasi_surat' => '000.2.3.1',
            'jabatan_resmi_sppb' => 'Kuasa Pengguna Barang',
            'format_kode_npb' => 'FORMAT II.I.6',
            'format_kode_spb' => 'FORMAT II.I.7',
            'format_kode_sppb' => 'FORMAT II.I.8',
        ]);
    }
}
