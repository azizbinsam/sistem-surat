<?php

use App\Exceptions\LanggananException;
use App\Models\HakTriwulan;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Services\AksesTriwulanService;
use App\Services\InvoiceService;
use App\Services\TahunAnggaranResolver;
use App\Support\Triwulan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    /** Nomor triwulan (1–4) yang dicentang sekolah. */
    public array $dipilih = [];
    public ?string $errorMsg = null;

    /**
     * Status tiap triwulan untuk Tahun Anggaran yang sedang dipilih.
     * status: dibeli | gratis | manual | menunggu | belum
     */
    protected function susunKartu(Sekolah $sekolah, TahunAnggaran $tahunAnggaran): array
    {
        $hak = HakTriwulan::where('sekolah_id', $sekolah->id)
            ->where('tahun_anggaran_id', $tahunAnggaran->id)
            ->get()
            ->keyBy('triwulan');

        $pending = InvoiceItem::with('invoice')
            ->whereHas('invoice', fn($q) => $q->where('sekolah_id', $sekolah->id)->whereIn('status', Invoice::PENDING))
            ->where('tahun_anggaran_id', $tahunAnggaran->id)
            ->get()
            ->keyBy('triwulan');

        $akses = app(AksesTriwulanService::class);

        $kartu = [];
        foreach ([1, 2, 3, 4] as $tw) {
            $h = $hak->get($tw);
            $aktif = $h && $h->status === 'aktif' && (!$h->berlaku_sampai || $h->berlaku_sampai->gte(today()));

            $kartu[$tw] = [
                'nama' => Triwulan::nama($tw),
                'status' => 'belum',
                'catatan' => null,
                'invoice' => null,
                'bisaDipilih' => false,
            ];

            if ($akses->gratisBerdasarkanAturan((int) $tahunAnggaran->tahun, $tw)) {
                // jendela gratis berbasis aturan: tidak bisa dipilih/dibeli
                $kartu[$tw]['status'] = 'gratis';
                $kartu[$tw]['catatan'] = 'Gratis — tidak perlu dibeli';
            } elseif ($aktif) {
                $kartu[$tw]['status'] = $h->sumber === 'berbayar' ? 'dibeli' : ($h->sumber === 'gratis' ? 'gratis' : 'manual');
                if ($h->berlaku_sampai) {
                    $kartu[$tw]['catatan'] = 'Gratis sampai ' . $h->berlaku_sampai->format('d-m-Y');
                }
            } elseif ($pending->has($tw)) {
                $kartu[$tw]['status'] = 'menunggu';
                $kartu[$tw]['invoice'] = $pending->get($tw)->invoice;
            } else {
                $kartu[$tw]['bisaDipilih'] = true;
                if ($h && $h->status === 'aktif') {
                    $kartu[$tw]['catatan'] = 'Masa gratis sudah berakhir';
                }
            }
        }

        return $kartu;
    }

    public function pilihSemuaSisa(TahunAnggaranResolver $resolver): void
    {
        $sekolah = auth()->user()->sekolah;
        $tahunAnggaran = $resolver->aktif($sekolah);

        $this->dipilih = $tahunAnggaran
            ? collect($this->susunKartu($sekolah, $tahunAnggaran))->filter(fn($k) => $k['bisaDipilih'])->keys()->all()
            : [];
    }

    public function buatInvoice(InvoiceService $service, TahunAnggaranResolver $resolver)
    {
        $this->errorMsg = null;

        $sekolah = auth()->user()->sekolah;
        $tahunAnggaran = $resolver->aktif($sekolah);

        if (!$tahunAnggaran) {
            $this->errorMsg = 'Tahun anggaran aktif tidak ditemukan.';
            return;
        }

        try {
            $invoice = $service->buatInvoice($sekolah, $tahunAnggaran, $this->dipilih);
        } catch (LanggananException $e) {
            $this->errorMsg = $e->getMessage();
            return;
        }

        $this->redirect(route('langganan.invoice', $invoice), navigate: true);
    }

    public function with(AksesTriwulanService $akses, TahunAnggaranResolver $resolver): array
    {
        $sekolah = auth()->user()->sekolah;
        $tahunAnggaran = $resolver->aktif($sekolah);
        $kartu = $tahunAnggaran ? $this->susunKartu($sekolah, $tahunAnggaran) : [];

        // abaikan centang yang sudah tidak valid (mis. triwulan baru saja dibeli/dipesan di tab lain)
        $dipilihValid = collect($this->dipilih)
            ->map(fn($t) => (int) $t)
            ->filter(fn($t) => $kartu[$t]['bisaDipilih'] ?? false)
            ->unique()
            ->values();

        return [
            'tahunAnggaran' => $tahunAnggaran,
            'kartu' => $kartu,
            'jumlahDipilih' => $dipilihValid->count(),
            'harga' => $akses->hitungHarga($dipilihValid->count()),
            'hargaTriwulan' => $akses->hargaTriwulan(),
            'hargaTahunan' => $akses->hargaTahunan(),
            'sisaKuota' => $akses->kuotaPercobaanSisa($sekolah),
            'kuotaTotal' => (int) config('langganan.kuota_percobaan'),
            'daftarInvoice' => Invoice::where('sekolah_id', $sekolah->id)->latest()->limit(10)->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900">Langganan</h1>
        <p class="text-sm text-zinc-500 mt-1">
            Beli paket untuk men-generate surat per triwulan.
            @if ($tahunAnggaran)
                Tahun Anggaran {{ $tahunAnggaran->tahun }}.
            @endif
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-100">
            {{ session('success') }}</div>
    @endif
    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-100">{{ $errorMsg }}</div>
    @endif

    <div class="mb-6 p-4 bg-zinc-50 border border-zinc-200 rounded-xl text-sm text-zinc-600 space-y-1">
        <p>Harga: <strong>Rp {{ number_format($hargaTriwulan, 0, ',', '.') }}</strong> per triwulan, atau
            <strong>Rp {{ number_format($hargaTahunan, 0, ',', '.') }}</strong> untuk 4 triwulan sekaligus.</p>
        <p>Input data, stok, dan arsip tetap bebas. Yang dibatasi hanya tombol <strong>Generate</strong> surat, sesuai
            triwulan dari tanggal transaksi.</p>
        <p>Kuota percobaan gratis: <strong>{{ $sisaKuota }} dari {{ $kuotaTotal }}</strong> generate tersisa.</p>
    </div>

    @if (!$tahunAnggaran)
        <p class="text-sm text-zinc-500">Tahun anggaran aktif belum tersedia.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            @foreach ($kartu as $tw => $k)
                <label wire:key="kartu-{{ $tw }}"
                    class="relative bg-white rounded-xl border p-5 {{ $k['bisaDipilih'] ? 'cursor-pointer hover:border-emerald-300' : 'opacity-90' }} {{ in_array($tw, array_map('intval', $dipilih), true) && $k['bisaDipilih'] ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-zinc-200' }}">
                    @if ($k['bisaDipilih'])
                        <input type="checkbox" wire:model.live="dipilih" value="{{ $tw }}"
                            class="absolute top-4 right-4 rounded text-emerald-600">
                    @endif
                    <p class="font-semibold text-zinc-900">{{ $k['nama'] }}</p>
                    <p class="mt-2 text-sm">
                        @if ($k['status'] === 'dibeli')
                            <span class="text-emerald-700 font-medium">✓ Sudah dibeli</span>
                        @elseif ($k['status'] === 'gratis')
                            <span class="text-emerald-700 font-medium">✓ Gratis</span>
                        @elseif ($k['status'] === 'manual')
                            <span class="text-emerald-700 font-medium">✓ Diberikan admin</span>
                        @elseif ($k['status'] === 'menunggu')
                            <span class="text-amber-700 font-medium">Menunggu pembayaran</span>
                        @else
                            <span class="text-zinc-500">Belum dibeli</span>
                        @endif
                    </p>
                    @if ($k['catatan'])
                        <p class="text-xs text-zinc-500 mt-1">{{ $k['catatan'] }}</p>
                    @endif
                    @if ($k['invoice'])
                        <a href="{{ route('langganan.invoice', $k['invoice']) }}" wire:navigate
                            class="text-xs text-emerald-600 underline mt-1 inline-block">Lihat invoice
                            {{ $k['invoice']->nomor }}</a>
                    @endif
                </label>
            @endforeach
        </div>

        <div class="mb-8 flex flex-col sm:flex-row sm:items-center gap-3 bg-white rounded-xl border border-zinc-200 p-4">
            <button type="button" wire:click="pilihSemuaSisa"
                class="text-sm text-emerald-700 font-medium hover:underline text-left">Pilih semua triwulan yang belum
                dibeli</button>
            <div class="sm:ml-auto text-sm text-zinc-600 sm:text-right">
                @if ($jumlahDipilih > 0)
                    <p>{{ $jumlahDipilih }} triwulan dipilih
                        @if ($harga['diskon'] > 0)
                            <span class="text-emerald-700">(hemat Rp {{ number_format($harga['diskon'], 0, ',', '.') }})</span>
                        @endif
                    </p>
                    <p class="font-semibold text-zinc-900">Total Rp {{ number_format($harga['total'], 0, ',', '.') }}</p>
                @else
                    <p>Centang triwulan yang ingin dibeli.</p>
                @endif
            </div>
            <button type="button" wire:click="buatInvoice" wire:loading.attr="disabled" wire:target="buatInvoice"
                @disabled($jumlahDipilih === 0)
                class="px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed">
                Buat Invoice
            </button>
        </div>
    @endif

    <h2 class="font-semibold text-zinc-900 mb-3">Riwayat Invoice</h2>
    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-100">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Nomor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Dibuat</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total
                        Bayar</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($daftarInvoice as $inv)
                    <tr wire:key="inv-{{ $inv->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900">{{ $inv->nomor }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $inv->created_at->format('d-m-Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right">Rp {{ number_format($inv->total_bayar, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span
                                class="px-2 py-0.5 text-xs rounded-full border {{ $inv->kelasBadge() }}">{{ $inv->labelStatus() }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a href="{{ route('langganan.invoice', $inv) }}" wire:navigate
                                class="text-emerald-600 hover:underline font-medium">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-zinc-500">Belum ada invoice.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-loading-overlay target="buatInvoice" pesan="Sedang membuat invoice, mohon tunggu…" />
</div>
