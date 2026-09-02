<?php

namespace App\Imports;

use App\Models\MasterBarang;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MasterBarangImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /** Kode barang yang dilewati (udah ada di database ATAU duplikat di dalam file yang sama). */
    protected array $dilewati = [];

    /** Kode barang yang udah lolos diproses di baris-baris sebelumnya (dalam file yang sama). */
    protected array $kodeSudahDiproses = [];

    protected int $jumlahDitambahkan = 0;

    public function __construct(protected int $sekolahId) {}

    public function model(array $row)
    {
        $kode = trim($row['kode_barang']);
        $normalized = strtolower($kode);

        // Duplikat di DALAM file excel yang sama -> skip, baris pertama yang menang.
        if (in_array($normalized, $this->kodeSudahDiproses, true)) {
            $this->dilewati[] = $kode;
            return null;
        }

        // Duplikat sama data yang udah ada di database. Query Eloquent biasa (bukan
        // Rule::unique mentah) -> otomatis ikut scope SoftDeletes, jadi barang yang
        // udah dihapus nggak lagi dianggap "ada" (ini yang jadi bug sebelumnya).
        $sudahAda = MasterBarang::where('sekolah_id', $this->sekolahId)
            ->whereRaw('LOWER(kode_barang) = ?', [$normalized])
            ->exists();

        if ($sudahAda) {
            $this->dilewati[] = $kode;
            return null;
        }

        $this->kodeSudahDiproses[] = $normalized;
        $this->jumlahDitambahkan++;

        return new MasterBarang([
            'sekolah_id' => $this->sekolahId,
            'kode_barang' => $kode,
            'nama_barang' => $row['nama_barang'],
            'kategori' => $row['kategori'] ?? null,
            'satuan_default' => $row['satuan_default'],
            'spesifikasi_default' => $row['spesifikasi_default'] ?? null,
        ]);
    }

    public function rules(): array
    {
        // Kode barang unik SENGAJA nggak dicek di sini lagi -- itu sekarang di-skip
        // (bukan gagal) lewat logic di model() di atas, biar baris lain di file yang
        // sama tetap diproses. Yang tetap wajib cuma field yang beneran nggak boleh
        // kosong buat bisa bikin barang baru sama sekali.
        return [
            'kode_barang' => ['required', 'string'],
            'nama_barang' => ['required', 'string'],
            'satuan_default' => ['required', 'string'],
        ];
    }

    public function jumlahDitambahkan(): int
    {
        return $this->jumlahDitambahkan;
    }

    public function dilewati(): array
    {
        return $this->dilewati;
    }
}
