<?php

use App\Models\BarangMasukItem;
use App\Models\KoreksiStok;
use App\Models\MasterBarang;
use App\Models\TransaksiItem;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public MasterBarang $masterBarang;
    public $ledger;

    public function mount(MasterBarang $masterBarang): void
    {
        if ($masterBarang->sekolah_id !== auth()->user()->sekolah_id) {
            abort(403);
        }

        $this->masterBarang = $masterBarang;

        $masuk = BarangMasukItem::where('master_barang_id', $masterBarang->id)->with('barangMasuk')->get()->map(
            fn($item) => [
                'tanggal' => $item->barangMasuk->tanggal,
                'jenis' => 'Masuk',
                'referensi' => 'BPU: ' . $item->barangMasuk->nomor_bpu,
                'jumlah' => $item->jumlah,
                'urutan' => $item->barangMasuk->id, // tie-breaker kronologis
            ],
        );

        $keluar = TransaksiItem::where('master_barang_id', $masterBarang->id)->with('transaksi')->get()->map(
            fn($item) => [
                'tanggal' => $item->transaksi->tanggal_npb,
                'jenis' => 'Keluar',
                'referensi' => 'Ref: ' . $item->transaksi->nomor_referensi_asal,
                'jumlah' => -$item->jumlah,
                'urutan' => $item->transaksi->id,
            ],
        );

        $koreksi = KoreksiStok::where('master_barang_id', $masterBarang->id)->get()->map(
            fn($item) => [
                'tanggal' => $item->tanggal,
                'jenis' => 'Koreksi',
                'referensi' => $item->alasan,
                'jumlah' => $item->jumlah,
                'urutan' => $item->id,
            ],
        );

        $gabungan = $masuk
            ->concat($keluar)
            ->concat($koreksi)
            ->sortBy([['tanggal', 'asc'], ['urutan', 'asc']])
            ->values();

        // hitung saldo berjalan
        $saldo = 0;
        $this->ledger = $gabungan->map(function ($row) use (&$saldo) {
            $saldo += $row['jumlah'];
            $row['saldo'] = $saldo;
            return $row;
        });
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900">Riwayat Persediaan: {{ $masterBarang->nama_barang }}</h2>
            <p class="text-sm text-gray-500">Kode: {{ $masterBarang->kode_barang }}</p>
        </div>
        <a href="{{ route('persediaan.index') }}" wire:navigate class="text-sm text-zinc-600 hover:underline">←
            Kembali</a>
    </div>

    <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Tanggal</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Jenis</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Referensi</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Jumlah</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($ledger as $row)
                    <tr>
                        <td class="px-4 py-2 text-sm">{{ \Carbon\Carbon::parse($row['tanggal'])->format('d-m-Y') }}</td>
                        <td class="px-4 py-2 text-sm">
                            <span
                                class="px-2 py-0.5 rounded text-xs
                                {{ $row['jenis'] === 'Masuk' ? 'bg-green-100 text-green-700' : ($row['jenis'] === 'Keluar' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ $row['jenis'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $row['referensi'] }}</td>
                        <td
                            class="px-4 py-2 text-sm text-right {{ $row['jumlah'] < 0 ? 'text-red-600' : 'text-green-700' }}">
                            {{ $row['jumlah'] > 0 ? '+' : '' }}{{ $row['jumlah'] }}
                        </td>
                        <td class="px-4 py-2 text-sm text-right font-semibold">{{ $row['saldo'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada riwayat untuk
                            barang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
