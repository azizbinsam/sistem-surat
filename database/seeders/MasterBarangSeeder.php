<?php

namespace Database\Seeders;

use App\Models\MasterBarang;
use App\Models\Sekolah;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sekolah = Sekolah::first();

        $barang = [
            ['kode_barang' => '1.1.7.01.03.0001', 'nama_barang' => 'Tinta Epson Hitam', 'kategori' => 'Bahan Komputer', 'satuan_default' => 'Botol'],
            ['kode_barang' => '1.1.7.01.01.0002', 'nama_barang' => 'Tipe Ex', 'kategori' => 'Alat Tulis Kantor', 'satuan_default' => 'Buah'],
            ['kode_barang' => '1.1.7.01.01.0003', 'nama_barang' => 'Map Kertas', 'kategori' => 'Alat Tulis Kantor', 'satuan_default' => 'Buah'],
            ['kode_barang' => '1.1.7.01.01.0004', 'nama_barang' => 'Kertas F4', 'kategori' => 'Alat Tulis Kantor', 'satuan_default' => 'Rim'],
        ];

        foreach ($barang as $item) {
            MasterBarang::create([...$item, 'sekolah_id' => $sekolah->id]);
        }
    }
}
