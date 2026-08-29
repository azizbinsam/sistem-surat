<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Tahun anggaran terbaru yang udah dibuka: <strong>{{ $this->getTahunTerbaru() ?? '-' }}</strong>
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
            Jumlah sekolah terdaftar: <strong>{{ $this->getJumlahSekolah() }}</strong>
        </p>
        <p class="text-xs text-gray-400 mt-4">
            Tahun anggaran baru cuma bisa dibuka dari sini (superadmin) — sekolah nggak punya
            kewenangan bikin tahun anggaran sendiri (PRD §12.3). Klik tombol di pojok kanan atas
            buat buka tahun anggaran berikutnya untuk semua sekolah sekaligus.
        </p>
    </div>
</x-filament-panels::page>
