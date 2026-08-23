<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\Sekolah;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sekolah = Sekolah::first();

        $pegawai = [
            [
                'nama' => 'Tulus Wahyudi, S.Pd',
                'nip' => '196604031993071001',
                'jabatan' => 'Kepala Sekolah',
                'kategori' => 'kepala_sekolah',
            ],
            [
                'nama' => 'Inayati, S.Pd',
                'nip' => null,
                'jabatan' => 'Pengurus Barang Pembantu',
                'kategori' => 'pengurus_barang_pembantu',
            ],
            [
                'nama' => 'Abdul Aziz',
                'nip' => null,
                'jabatan' => 'Operator Layanan Operasional',
                'kategori' => 'tendik',
            ],
            [
                'nama' => 'Siti Rahma, S.Pd',
                'nip' => null,
                'jabatan' => 'Guru Kelas',
                'kategori' => 'guru',
            ],
        ];

        foreach ($pegawai as $item) {
            Pegawai::create([...$item, 'sekolah_id' => $sekolah->id]);
        }
    }
}
