<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Jumlah sekolah terdaftar: <strong>{{ $this->getJumlahSekolah() }}</strong>
        </p>
        <p class="text-xs text-gray-400 mt-2">
            Tahun anggaran baru cuma bisa dibuka dari sini (superadmin) — sekolah nggak punya
            kewenangan bikin tahun anggaran sendiri (PRD §12.3). Tombol "Buka" di pojok kanan atas
            cuma bikin barisnya dengan status <strong>Hold</strong> (belum aktif) — sekolah tetap
            jalan normal sampai kamu klik "Aktifkan" di tabel bawah. Kalau salah aktifin tahun,
            tinggal klik "Aktifkan" lagi di tahun yang seharusnya — otomatis balik (rollback).
        </p>
    </div>

    <div
        class="fi-section mt-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tahun</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Jumlah Sekolah</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->getRingkasanTahun() as $baris)
                    <tr wire:key="tahun-{{ $baris->tahun }}">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $baris->tahun }}</td>
                        <td class="px-4 py-3">
                            @if ($baris->jumlah_aktif == $baris->jumlah_sekolah)
                                <span
                                    class="inline-flex items-center rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-400/10 dark:text-success-400">
                                    Aktif ({{ $baris->jumlah_aktif }}/{{ $baris->jumlah_sekolah }})
                                </span>
                            @elseif ($baris->jumlah_aktif > 0)
                                <span
                                    class="inline-flex items-center rounded-full bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">
                                    Campuran: {{ $baris->jumlah_aktif }} aktif, {{ $baris->jumlah_hold }} hold
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-400/10 dark:text-gray-400">
                                    Hold
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $baris->jumlah_sekolah }} sekolah</td>
                        <td class="px-4 py-3 text-right">
                            @if ($baris->jumlah_aktif < $baris->jumlah_sekolah)
                                <button wire:click="aktifkanTahun({{ $baris->tahun }})"
                                    wire:confirm="Aktifkan Tahun Anggaran {{ $baris->tahun }} untuk semua sekolah? Tahun anggaran yang sekarang aktif di tiap sekolah akan otomatis turun jadi Hold."
                                    type="button"
                                    class="fi-btn fi-btn-size-sm inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">
                                    Aktifkan untuk Semua Sekolah
                                </button>
                            @else
                                <span class="text-xs text-gray-400">Sudah aktif semua</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">
                            Belum ada tahun anggaran yang dibuka.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
