<?php

return [
    /*
    | Saklar pengunci generate per triwulan (doc/LANGGANAN.md §4).
    |
    | false (default) = semua sekolah bebas generate, tidak ada pengecekan hak triwulan.
    | Biarkan false sampai perintah `langganan:beri-hak-gratis-2026` (Fase 28) dijalankan
    | di produksi, baru ubah jadi true (LANGGANAN_PENGUNCI_AKTIF=true di .env) — kalau
    | dinyalakan lebih dulu, sekolah lama akan menghabiskan kuota percobaan dan terblokir.
    */
    'pengunci_aktif' => env('LANGGANAN_PENGUNCI_AKTIF', false),

    // Percobaan gratis untuk sekolah baru: jumlah transaksi yang boleh di-generate tanpa hak triwulan.
    'kuota_percobaan' => 3,

    // Batas waktu bayar invoice (hari) — juga dipakai saat invoice ditolak & sekolah boleh upload ulang.
    'jatuh_tempo_hari' => 7,

    // Rentang kode unik di belakang nominal invoice (mis. Rp39.017) supaya mudah dicocokkan di mutasi rekening.
    'kode_unik_min' => 1,
    'kode_unik_max' => 999,
];
