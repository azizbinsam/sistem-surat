<?php

namespace App\Imports;

use App\Models\MasterBarang;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class MasterBarangImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(protected int $sekolahId) {}

    public function model(array $row)
    {
        return new MasterBarang([
            'sekolah_id' => $this->sekolahId,
            'kode_barang' => $row['kode_barang'],
            'nama_barang' => $row['nama_barang'],
            'kategori' => $row['kategori'] ?? null,
            'satuan_default' => $row['satuan_default'],
        ]);
    }

    public function rules(): array
    {
        return [
            'kode_barang' => ['required', 'string'],
            'nama_barang' => ['required', 'string'],
            'satuan_default' => ['required', 'string'],
        ];
    }
}
