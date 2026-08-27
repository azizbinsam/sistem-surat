<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $tabel = ['master_barang', 'pegawai', 'barang_masuk', 'transaksi', 'koreksi_stok'];

    public function up(): void
    {
        $sekolahList = DB::table('sekolah')->get();

        foreach ($sekolahList as $sekolah) {
            $tahunAnggaranId = DB::table('tahun_anggaran')
                ->where('sekolah_id', $sekolah->id)
                ->value('id');

            if (!$tahunAnggaranId) {
                $tahunAnggaranId = DB::table('tahun_anggaran')->insertGetId([
                    'sekolah_id' => $sekolah->id,
                    'tahun' => now()->year,
                    // nomor_urut_terakhir lama di kolom sekolah dibawa ke tahun anggaran default ini,
                    // biar penomoran surat yang sudah jalan nggak keputus/kebentrok.
                    'nomor_urut_terakhir' => $sekolah->nomor_urut_terakhir ?? 0,
                    'is_aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($this->tabel as $nama) {
                DB::table($nama)
                    ->where('sekolah_id', $sekolah->id)
                    ->whereNull('tahun_anggaran_id')
                    ->update(['tahun_anggaran_id' => $tahunAnggaranId]);
            }
        }
    }

    public function down(): void
    {
        // Data migration tidak di-reverse (menghapus tahun_anggaran_id yang sudah terisi
        // berisiko menghapus histori) — biarkan kosong, cukup di-no-op.
    }
};
