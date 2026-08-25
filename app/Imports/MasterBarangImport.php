<?php

namespace App\Imports;

use App\Models\MasterBarang;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class MasterBarangImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    // nampung kode_barang yang udah lolos diproses di baris-baris sebelumnya (dalam file yang sama)
    protected array $kodeSudahDiproses = [];

    public function __construct(protected int $sekolahId) {}

    public function model(array $row)
    {
        $this->kodeSudahDiproses[] = strtolower(trim($row['kode_barang']));

        return new MasterBarang([
            'sekolah_id' => $this->sekolahId,
            'kode_barang' => $row['kode_barang'],
            'nama_barang' => $row['nama_barang'],
            'kategori' => $row['kategori'] ?? null,
            'satuan_default' => $row['satuan_default'],
            'spesifikasi_default' => $row['spesifikasi_default'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'kode_barang' => [
                'required',
                'string',
                // bentrok sama data yang UDAH ADA di database
                Rule::unique('master_barang', 'kode_barang')->where('sekolah_id', $this->sekolahId),
                // bentrok sama baris LAIN di file excel yang sama
                function ($attribute, $value, $fail) {
                    $normalized = strtolower(trim($value));
                    if (in_array($normalized, $this->kodeSudahDiproses)) {
                        $fail("Kode barang \"{$value}\" duplikat di dalam file excel ini (muncul lebih dari sekali).");
                    }
                },
            ],
            'nama_barang' => ['required', 'string'],
            'satuan_default' => ['required', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'kode_barang.unique' => 'Kode barang :input sudah terdaftar di Master Barang sekolah ini.',
        ];
    }
}
