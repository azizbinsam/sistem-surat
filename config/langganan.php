<?php

return [
    /*
    | Saklar pengunci generate per triwulan (doc/LANGGANAN.md §4).
    |
    | true (default sejak peluncuran ulang) = generate surat butuh hak triwulan (atau kuota percobaan),
    | kecuali triwulan yang masuk jendela gratis di bawah. false hanya untuk keadaan khusus/uji coba:
    | semua sekolah bebas generate dan menu Langganan disembunyikan.
    */
    'pengunci_aktif' => env('LANGGANAN_PENGUNCI_AKTIF', true),

    /*
    | Jendela GRATIS berbasis aturan (bukan baris data): semua triwulan sampai (tahun, sampai_triwulan)
    | tidak perlu dibeli — berlaku untuk SEMUA sekolah, termasuk yang mendaftar belakangan, dan tanpa batas
    | waktu, dihitung dari tahun anggaran + triwulan transaksi.
    | Contoh bawaan: Tahun Anggaran 2026 triwulan 1–3 gratis; mulai TW4 2026 berbayar.
    | tahun = 0 mematikan jendela gratis (dipakai di phpunit.xml supaya test tidak tergantung tanggal).
    */
    'gratis' => [
        'tahun' => (int) env('LANGGANAN_GRATIS_TAHUN', 2026),
        'sampai_triwulan' => (int) env('LANGGANAN_GRATIS_SAMPAI_TW', 3),
        'tampil_di_landing' => true,
    ],

    // Percobaan gratis untuk sekolah baru: jumlah transaksi yang boleh di-generate tanpa hak triwulan.
    'kuota_percobaan' => 3,

    // Batas waktu bayar invoice (hari) — juga dipakai saat invoice ditolak & sekolah boleh upload ulang.
    'jatuh_tempo_hari' => 7,

    // Rentang kode unik di belakang nominal invoice (mis. Rp39.017) supaya mudah dicocokkan di mutasi rekening.
    'kode_unik_min' => 1,
    'kode_unik_max' => 999,

    // Identitas penerbit di kwitansi PDF.
    'penerbit' => [
        'nama' => env('LANGGANAN_PENERBIT_NAMA', 'Delix Studio'),
        'situs' => env('LANGGANAN_PENERBIT_SITUS', 'delixstudio.com'),
    ],
];
