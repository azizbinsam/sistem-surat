<?php

namespace App\Livewire\Concerns;

use App\Services\TahunAnggaranResolver;
use App\Support\Triwulan;
use Carbon\Carbon;

/**
 * Filter "rentang tanggal + pilih triwulan" yang dipakai bareng oleh beberapa halaman daftar
 * (Penerimaan Barang, Pengeluaran Barang). Tinggal `use FiltersByPeriode;` di komponen Volt,
 * lalu panggil terapkanFilterPeriode($query, 'nama_kolom_tanggal') di method with().
 *
 * Cara kerja:
 *  - Pilih triwulan  -> tanggal awal & akhir terisi otomatis (dari tahun anggaran yang sedang dipilih).
 *  - Ubah tanggal manual -> pilihan triwulan dikosongkan (karena rentangnya sudah bukan triwulan murni).
 */
trait FiltersByPeriode
{
    public string $triwulan = '';
    public string $tanggalAwal = '';
    public string $tanggalAkhir = '';

    /** Hook Livewire: otomatis jalan saat properti $triwulan berubah dari browser. */
    public function updatedTriwulan($value): void
    {
        if (in_array((string) $value, ['1', '2', '3', '4'], true)) {
            [$awal, $akhir] = Triwulan::rentang($this->tahunPeriode(), (int) $value);
            $this->tanggalAwal = $awal->toDateString();
            $this->tanggalAkhir = $akhir->toDateString();
        } else {
            $this->triwulan = '';
            $this->tanggalAwal = '';
            $this->tanggalAkhir = '';
        }

        $this->setelahFilterPeriodeBerubah();
    }

    public function updatedTanggalAwal(): void
    {
        $this->triwulan = '';
        $this->setelahFilterPeriodeBerubah();
    }

    public function updatedTanggalAkhir(): void
    {
        $this->triwulan = '';
        $this->setelahFilterPeriodeBerubah();
    }

    public function resetFilterPeriode(): void
    {
        $this->triwulan = '';
        $this->tanggalAwal = '';
        $this->tanggalAkhir = '';
        $this->setelahFilterPeriodeBerubah();
    }

    /** Balik ke halaman 1 & kosongkan centang — dipanggil tiap filter periode berubah. */
    protected function setelahFilterPeriodeBerubah(): void
    {
        $this->resetPage();

        if (property_exists($this, 'selected')) {
            $this->selected = [];
        }
    }

    /** Tahun dari Tahun Anggaran yang sedang dipilih user (fallback: tahun berjalan). */
    protected function tahunPeriode(): int
    {
        $tahunAnggaran = app(TahunAnggaranResolver::class)->aktif(auth()->user()->sekolah);

        return (int) ($tahunAnggaran->tahun ?? now()->year);
    }

    public function sedangFilterPeriode(): bool
    {
        return $this->tanggalAwal !== '' || $this->tanggalAkhir !== '';
    }

    /** Terapkan filter ke query. Input dari browser divalidasi formatnya dulu (Y-m-d), yang aneh diabaikan. */
    protected function terapkanFilterPeriode($query, string $kolomTanggal)
    {
        $awal = $this->parseTanggalFilter($this->tanggalAwal);
        $akhir = $this->parseTanggalFilter($this->tanggalAkhir);

        return $query
            ->when($awal, fn($q) => $q->whereDate($kolomTanggal, '>=', $awal->toDateString()))
            ->when($akhir, fn($q) => $q->whereDate($kolomTanggal, '<=', $akhir->toDateString()));
    }

    protected function parseTanggalFilter(string $nilai): ?Carbon
    {
        if ($nilai === '' || !Carbon::hasFormat($nilai, 'Y-m-d')) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay();
    }
}
