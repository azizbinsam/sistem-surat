<?php

use App\Exceptions\LanggananException;
use App\Models\Invoice;
use App\Models\RekeningDonasi;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Invoice $invoice;
    public $bukti = null;
    public ?string $errorMsg = null;

    public function mount(Invoice $invoice): void
    {
        // proteksi tenant: sekolah hanya boleh membuka invoice miliknya sendiri
        if ($invoice->sekolah_id !== auth()->user()->sekolah_id) {
            abort(404);
        }

        $this->invoice = $invoice->load('items.tahunAnggaran');
    }

    public function kirimBukti(InvoiceService $service): void
    {
        $this->errorMsg = null;

        $this->validate(
            ['bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048']],
            [],
            ['bukti' => 'bukti transfer'],
        );

        try {
            $service->uploadBukti($this->invoice->fresh(), $this->bukti);
        } catch (LanggananException $e) {
            $this->errorMsg = $e->getMessage();
            return;
        }

        $this->bukti = null;
        $this->invoice = $this->invoice->fresh()->load('items.tahunAnggaran');
        session()->flash('success', 'Bukti transfer terkirim. Admin akan memverifikasinya (maksimal 1×24 jam kerja).');
    }

    public function batalkan(InvoiceService $service)
    {
        $this->errorMsg = null;

        try {
            $service->batalkan($this->invoice->fresh());
        } catch (LanggananException $e) {
            $this->errorMsg = $e->getMessage();
            return;
        }

        session()->flash('success', 'Invoice dibatalkan.');
        $this->redirect(route('langganan.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'rekening' => RekeningDonasi::orderBy('urutan')->get(),
            'urlBukti' => $this->invoice->bukti_transfer ? Storage::disk('public')->url($this->invoice->bukti_transfer) : null,
        ];
    }
}; ?>

<div class="max-w-3xl">
    <a href="{{ route('langganan.index') }}" wire:navigate class="text-sm text-zinc-500 hover:text-emerald-600">← Kembali ke
        Langganan</a>

    <div class="flex items-start justify-between gap-4 mt-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Invoice {{ $invoice->nomor }}</h1>
            <p class="text-sm text-zinc-500 mt-1">Dibuat {{ $invoice->created_at->format('d-m-Y H:i') }}</p>
        </div>
        <span
            class="px-3 py-1 text-sm rounded-full border {{ $invoice->kelasBadge() }}">{{ $invoice->labelStatus() }}</span>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-100">
            {{ session('success') }}</div>
    @endif
    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-100">{{ $errorMsg }}</div>
    @endif

    @if ($invoice->status === \App\Models\Invoice::DITOLAK && $invoice->catatan_admin)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-100">
            <p class="font-medium">Bukti transfer ditolak admin:</p>
            <p>{{ $invoice->catatan_admin }}</p>
            <p class="mt-1">Silakan upload ulang bukti yang benar sebelum {{ $invoice->jatuh_tempo_at->format('d-m-Y') }}.</p>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-zinc-200 p-5 mb-4">
        <h2 class="font-semibold text-zinc-900 mb-3">Rincian</h2>
        <ul class="divide-y divide-zinc-100 text-sm">
            @foreach ($invoice->items as $item)
                <li class="flex justify-between py-2">
                    <span>Triwulan {{ $item->triwulan }} — Tahun Anggaran {{ $item->tahunAnggaran->tahun }}</span>
                    <span>Rp {{ number_format($item->harga, 0, ',', '.') }}</span>
                </li>
            @endforeach
        </ul>
        <div class="border-t border-zinc-200 mt-2 pt-3 text-sm space-y-1">
            @if ($invoice->diskon > 0)
                <div class="flex justify-between text-emerald-700"><span>Diskon paket</span><span>− Rp
                        {{ number_format($invoice->diskon, 0, ',', '.') }}</span></div>
            @endif
            <div class="flex justify-between"><span>Subtotal</span><span>Rp
                    {{ number_format($invoice->total, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span>Kode unik</span><span>{{ $invoice->kode_unik }}</span></div>
            <div class="flex justify-between text-base font-bold text-zinc-900"><span>Total yang harus
                    ditransfer</span><span>Rp {{ number_format($invoice->total_bayar, 0, ',', '.') }}</span></div>
        </div>
        <p class="text-xs text-zinc-500 mt-3">Transfer <strong>tepat sesuai nominal di atas</strong> (termasuk kode unik)
            agar pembayaran mudah dicocokkan. Batas bayar: {{ $invoice->jatuh_tempo_at->format('d-m-Y H:i') }}.</p>
    </div>

    @if ($invoice->sedangBerjalan() || $invoice->status === \App\Models\Invoice::DITOLAK)
        <div class="bg-white rounded-xl border border-zinc-200 p-5 mb-4">
            <h2 class="font-semibold text-zinc-900 mb-3">Rekening Tujuan</h2>
            @forelse ($rekening as $rek)
                <div class="flex items-center gap-4 py-2 {{ !$loop->first ? 'border-t border-zinc-100' : '' }}">
                    @if ($rek->foto)
                        <img src="{{ asset('storage/' . $rek->foto) }}" alt="{{ $rek->nama_bank }}"
                            class="w-16 h-16 object-cover rounded-lg border border-zinc-200">
                    @endif
                    <div class="text-sm">
                        <p class="font-semibold text-zinc-900">{{ $rek->nama_bank }}</p>
                        <p class="text-zinc-700 text-base tracking-wide">{{ $rek->nomor_rekening }}</p>
                        <p class="text-zinc-500">a.n. {{ $rek->atas_nama }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-zinc-500">Rekening tujuan belum diatur admin. Hubungi admin untuk informasi pembayaran.
                </p>
            @endforelse
        </div>
    @endif

    @if ($urlBukti)
        <div class="bg-white rounded-xl border border-zinc-200 p-5 mb-4 text-sm">
            <p class="font-semibold text-zinc-900 mb-1">Bukti transfer terkirim</p>
            <a href="{{ $urlBukti }}" target="_blank" class="text-emerald-600 underline">Lihat bukti</a>
            @if ($invoice->dibayar_at)
                <span class="text-zinc-500"> — diunggah {{ $invoice->dibayar_at->format('d-m-Y H:i') }}</span>
            @endif
        </div>
    @endif

    @if ($invoice->bisaUploadBukti())
        <div class="bg-white rounded-xl border border-zinc-200 p-5 mb-4">
            <h2 class="font-semibold text-zinc-900 mb-3">
                {{ $invoice->status === \App\Models\Invoice::DITOLAK ? 'Upload Ulang Bukti Transfer' : 'Upload Bukti Transfer' }}
            </h2>
            <input type="file" wire:model="bukti" accept=".jpg,.jpeg,.png,.pdf"
                class="block w-full text-sm text-zinc-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:text-zinc-700">
            @error('bukti')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-zinc-500 mt-1">Format JPG, PNG, atau PDF. Maksimal 2 MB.</p>
            <button type="button" wire:click="kirimBukti" wire:loading.attr="disabled" wire:target="kirimBukti,bukti"
                class="mt-3 px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-500 disabled:opacity-50">
                Kirim Bukti
            </button>
        </div>
    @endif

    @if ($invoice->status === \App\Models\Invoice::LUNAS)
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">
            Pembayaran sudah diverifikasi. Triwulan di invoice ini sudah aktif — silakan kembali ke menu Pengeluaran
            Barang untuk generate surat.
            <a href="{{ route('langganan.kwitansi', $invoice) }}"
                class="block mt-2 font-semibold underline">Unduh Kwitansi (PDF)</a>
        </div>
    @endif

    @if ($invoice->bisaDibatalkan())
        <div class="mt-4">
            <button type="button" wire:click="batalkan" wire:confirm="Batalkan invoice ini?"
                class="text-sm text-red-600 hover:underline">Batalkan invoice</button>
        </div>
    @endif

    <x-loading-overlay target="kirimBukti" pesan="Sedang mengirim bukti transfer, mohon tunggu…" />
</div>
