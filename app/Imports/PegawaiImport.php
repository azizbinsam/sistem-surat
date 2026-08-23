<?php

namespace App\Imports;

use App\Models\Pegawai;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class PegawaiImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    protected array $mapKategori = [
        'kepala sekolah' => 'kepala_sekolah',
        'pengurus barang pembantu' => 'pengurus_barang_pembantu',
        'guru' => 'guru',
        'tendik' => 'tendik',
    ];

    public function __construct(protected int $sekolahId) {}

    public function model(array $row)
    {
        $kategoriMentah = strtolower(trim($row['kategori_pegawai']));

        return new Pegawai([
            'sekolah_id' => $this->sekolahId,
            'nama' => $row['nama'],
            'nip' => $row['nip'] ?? null,
            'jabatan' => $row['jabatan'],
            'kategori' => $this->mapKategori[$kategoriMentah] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string'],
            'jabatan' => ['required', 'string'],
            'kategori_pegawai' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! isset($this->mapKategori[strtolower(trim($value))])) {
                    $fail('Kategori harus salah satu dari: Kepala Sekolah, Pengurus Barang Pembantu, Guru, Tendik.');
                }
            }],
        ];
    }
}