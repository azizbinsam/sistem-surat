# LANGGANAN.md — Model Langganan Triwulan (v2.0)

Dokumen ini adalah **sumber kebenaran** untuk model bisnis v2.0 dan **menggantikan** PRD §12.1 (gratis + donasi) serta Fase 9 lama (Fersaku). Kalau ada yang bertentangan dengan `PRD.md`, dokumen ini yang berlaku.

Terakhir diperbarui: 17 November 2026.

---

## 1. Ringkasan Keputusan

| Hal | Keputusan |
|---|---|
| Unit yang dijual | **Hak generate surat per triwulan** (bukan per waktu/hari) |
| Paket Triwulan | **Rp39.000** per triwulan |
| Paket Tahunan | **Rp129.000** = 4 triwulan sekaligus (hemat Rp27.000 dari 4 × Rp39.000 = Rp156.000) |
| Paket Bulanan | **Dihapus** |
| Yang dikunci | Hanya **Generate** surat. Input data, stok, dashboard, dan arsip tetap bebas |
| Format surat | **Word saja**. Generate PDF untuk surat dihapus |
| Pembayaran awal | **Manual** (transfer + upload bukti + verifikasi admin) |
| Payment gateway | **Fersaku dibatalkan.** Midtrans tidak dipakai. Otomatisasi nanti pakai **iPaymu** (Fase 29, opsional) |
| Sekolah lama | Gratis untuk Tahun Anggaran 2026. Pembayaran mulai berlaku **2027** |
| Kelonggaran | TW1–TW3 2026: gratis sampai **31 Jan 2027**. TW4 2026: gratis sampai **28 Feb 2027** |
| Percobaan sekolah baru | **3 generate gratis** per sekolah, seumur akun |
| Batas waktu invoice | **7 hari** |

## 2. Prinsip

1. **Nilai yang dijual = surat, bukan data.** Sekolah bebas input dan melihat data kapan pun, karena stok dihitung berantai (sisa TW2 bergantung pada transaksi TW1). Kalau input dikunci per triwulan, ledger dan kolom "sisa sebelum transaksi" di SPB jadi salah.
2. **Data tidak pernah disandera.** Tidak ada langganan yang membuat data terkunci atau terhapus.
3. **Satu aturan untuk semua.** Sekolah lama, sekolah baru, dan paket Tahunan memakai satu mekanisme yang sama: tabel `hak_triwulan`. Tidak ada cabang kode khusus "sekolah gratis".
4. **Manual dulu, otomatis belakangan.** Alur manual dan iPaymu hanya berbeda di langkah konfirmasi pembayaran. Invoice, hak triwulan, dan kwitansi sama persis.

## 3. Konsep: Hak Triwulan

Satu **hak triwulan** = izin men-generate surat untuk transaksi yang **tanggalnya** jatuh di triwulan tertentu pada Tahun Anggaran tertentu.

| Triwulan | Bulan |
|---|---|
| TW1 | Jan–Mar |
| TW2 | Apr–Jun |
| TW3 | Jul–Sep |
| TW4 | Okt–Des |

Asumsi: Tahun Anggaran = Januari–Desember. Pemetaan tanggal ke triwulan ada di satu tempat: `App\Support\Triwulan`.

**Yang menentukan triwulan adalah `tanggal_npb` transaksi, bukan kapan tombol Generate ditekan.** Laporan TW1 biasanya dibuat April atau Mei; sekolah yang membeli TW1 di bulan Mei tetap bisa men-generate transaksi Januari–Maret.

**Aturan harga** (dihitung saat membuat invoice):

```
harga = min( jumlah_triwulan_yang_belum_dibeli × 39.000 , 129.000 )
```

Contoh: beli 1 triwulan = Rp39.000. Beli 3 triwulan = Rp117.000. Beli 4 triwulan sekaligus = Rp129.000 (berlabel "Tahunan"). Sekolah yang sudah punya TW1 dan membeli sisanya membayar 3 × Rp39.000 = Rp117.000.

## 4. Aturan Akses

> **Saklar peluncuran:** pengunci hanya berlaku bila `LANGGANAN_PENGUNCI_AKTIF=true` di `.env` (config `langganan.pengunci_aktif`, default **false**). Selama saklar mati, semua sekolah bebas generate, kuota percobaan tidak terpakai, dan menu **Langganan** tidak tampil di sidebar. Nyalakan **setelah** `langganan:beri-hak-gratis-2026` dijalankan di produksi (Fase 28); kalau dinyalakan lebih dulu, sekolah lama akan menghabiskan kuota percobaan dan terblokir.

| Aksi | Perlu hak triwulan? |
|---|---|
| Input / edit / hapus penerimaan & pengeluaran barang | Tidak |
| Lihat persediaan, riwayat, dashboard | Tidak |
| **Generate** (satuan & bulk) | **Ya**, untuk triwulan dari `tanggal_npb` |
| **Unduh Word** | Tidak, cukup transaksi sudah berstatus "sudah di-generate" |
| **Batal Generate** | Tidak |
| Generate ulang setelah batal di triwulan yang sudah dibeli | Tidak memotong apa pun (yang dibeli adalah hak, bukan jumlah generate) |

Pengecekan hak dipasang di `jalankanGenerate()` pada `transaksi/index.blade.php` (sudah ada penanda komentar di kodenya). Bulk generate: kalau ada transaksi di triwulan yang belum dibeli, **seluruh proses dibatalkan** dan pesan menyebut triwulan mana yang perlu dibeli — konsisten dengan aturan pihak peminta (§6).

**Kuota percobaan:** transaksi di triwulan yang belum punya hak boleh tetap di-generate selama kuota (3) masih cukup untuk **seluruh** transaksi yang diproses (all-or-nothing). Kuota dipakai per transaksi yang berhasil di-generate, dicatat dalam transaksi database yang sama, dan **tidak dikembalikan** oleh Batal Generate.

Hak dianggap **aktif** bila: `status = 'aktif'` DAN (`berlaku_sampai` kosong ATAU `berlaku_sampai >= hari ini`).

## 5. Alur Sekolah

1. Sekolah menginput barang masuk dan keluar seperti biasa. Transaksi berstatus **Draft**.
2. Saat mau membuat laporan, sekolah klik **Generate** (satuan atau terpilih).
3. Kalau triwulannya belum dibeli, muncul pesan: "N transaksi TWx belum bisa di-generate. Beli paket TWx dulu." plus tombol ke halaman **Langganan**. Transaksi tetap aman sebagai draft.
4. Di halaman **Langganan**: empat kartu TW1–TW4 untuk Tahun Anggaran terpilih, masing-masing berstatus *Belum dibeli* / *Dibeli* / *Gratis (s/d tanggal)*. Sekolah mencentang triwulan yang mau dibeli. Ada pintasan **Beli semua sisa triwulan**.
5. Sistem membuat **invoice**: nomor `INV-{tahun}-{urut}`, total harga + **kode unik 3 digit** (misal Rp39.017) untuk memudahkan mencocokkan mutasi rekening, rekening tujuan, dan batas bayar 7 hari.
6. Sekolah transfer, lalu upload **bukti transfer** di halaman invoice. Status → *Menunggu verifikasi*.
7. Admin memeriksa dan klik **Setujui** (target maksimal 1×24 jam kerja). Sistem membuat hak triwulan (aktif), mengirim email, dan membuat **kwitansi PDF** (dompdf dipertahankan khusus untuk ini).
8. Sekolah kembali Generate. Setelah **sudah di-generate**, tombol **Unduh Word** aktif di menu aksi baris. Bulk: **Unduh Word (ZIP)**.

### Status invoice

`menunggu_pembayaran` → `menunggu_verifikasi` → `lunas` | `ditolak` (admin menulis alasan; sekolah bisa upload ulang). Tambahan: `kedaluwarsa` (lewat batas bayar tanpa bukti) dan `dibatalkan` (oleh sekolah).

## 6. Generate vs Unduh (perubahan halaman Pengeluaran Barang)

Dulu satu klik "Generate Word" = beri nomor + buat file + unduh. Sekarang dipecah:

| Tombol | Efek |
|---|---|
| **Generate** | Beri nomor NPB/SPB/SPPB, status → `selesai` (tampil "Sudah di-generate"). **Tidak** membuat file |
| **Unduh Word** | Buat file dari data yang ada lalu unduh. Hanya untuk transaksi sudah di-generate |
| **Batal Generate** | (sebelumnya "Batalkan Nomor") Kosongkan nomor, status → `draft`. Counter hanya turun bila itu nomor terakhir; nomor di tengah hilang permanen (sudah dijelaskan di modal) |

Tombol di baris menyesuaikan status: *Draft* → Edit, Generate, Hapus. *Sudah di-generate* → Edit, Unduh Word, Batal Generate, Hapus. Bar aksi bulk: Generate Terpilih, Unduh Word (ZIP), Batal Generate Terpilih, Hapus Terpilih.

**Aturan bulk generate:** kalau ada transaksi (yang belum di-generate) yang pihak pemintanya belum dipilih, **seluruh proses dibatalkan** dan pesan menyebut nomor referensi yang bermasalah. Transaksi yang sudah di-generate dalam pilihan dilewati dan dilaporkan jumlahnya.

**Loader:** selama Generate / Unduh berjalan, muncul overlay "Sedang generate nomor surat, mohon tunggu…" atau "Sedang menyiapkan file Word, mohon tunggu…". Overlay juga mencegah klik ganda.

**Keamanan penomoran:** `NomorSuratService::generateNomorNpb()` mengunci baris `tahun_anggaran` (`lockForUpdate`) di dalam transaksi database, dan bulk generate dibungkus satu `DB::transaction` — dua proses tidak bisa mendapat nomor yang sama, dan kegagalan di tengah jalan membatalkan semuanya.

## 7. Model Data (tabel baru, Fase 24+)

**`hak_triwulan`**

| Kolom | Keterangan |
|---|---|
| `sekolah_id`, `tahun_anggaran_id`, `triwulan` (1–4) | unique bersama |
| `sumber` | `berbayar` \| `gratis` \| `manual` (diberikan admin) |
| `invoice_item_id` | nullable (kosong untuk gratis/manual) |
| `berlaku_sampai` | nullable. Batas waktu generate (dipakai hak gratis 2026) |
| `status` | `aktif` \| `dicabut` |

**`invoice`**: `nomor`, `sekolah_id`, `total`, `kode_unik`, `total_bayar`, `status`, `metode` (`manual` \| `ipaymu`), `bukti_transfer`, `catatan_admin`, `jatuh_tempo_at`, `dibayar_at`, `diverifikasi_oleh`, `diverifikasi_at`.

**`invoice_item`**: `invoice_id`, `tahun_anggaran_id`, `triwulan`, `harga`.

**`banner`**: lihat §11.

**`users`**: tambah `last_login_at`.

**`paket_langganan`** (katalog harga, menggantikan `paket_subscription`): `kode` (`triwulan` | `tahunan`), `nama`, `harga` (rupiah, integer), `jumlah_triwulan`. Harga awal Rp39.000 dan Rp129.000 diisi oleh migrasi dan bisa diubah admin lewat menu **Paket & Harga** (berlaku untuk invoice yang dibuat setelahnya). Tabel lama `subscriptions` dan `paket_subscription` (termasuk kolom `fersaku_payment_id`) dihapus oleh migrasi Fase 24.

**`sekolah.generate_percobaan_terpakai`**: hitungan kuota percobaan (batas di `config/langganan.php`).

**Kolom `invoice` tambahan:** `subtotal`, `diskon`, `total` (hasil rumus harga), lalu `kode_unik` dan `total_bayar = total + kode_unik`. Nomor invoice: `INV-{tahun}-{id 4 digit}`.

## 8. Sekolah Lama (migrasi ke model baru)

Satu perintah artisan, **aman dijalankan berulang** (idempotent): `php artisan langganan:beri-hak-gratis-2026`

- Untuk setiap sekolah yang terdaftar sampai 31 Des 2026 dan memiliki Tahun Anggaran 2026: buat hak triwulan `sumber = gratis` untuk TW1–TW4.
- `berlaku_sampai`: TW1–TW3 → **2027-01-31**; TW4 → **2027-02-28** (laporan TW4 biasanya dibuat Januari–Februari).
- Setelah batas lewat dan belum di-generate, sekolah perlu membeli seperti biasa.
- **Pengumuman ke sekolah** (email + banner dashboard) paling lambat awal Desember 2026: sampai kapan gratis, harga paket, dan cara bayar.

## 9. Email & Pengingat Otomatis

Memakai Laravel Scheduler (satu cron job di cPanel: `* * * * * php /path/artisan schedule:run`). Semua email memakai satu Mailable (`NotifikasiLanggananMail`) dan layout `emails.layout`. **Gagal kirim email tidak pernah menggagalkan aksi utama** (mis. persetujuan pembayaran): error hanya dicatat di log.

| Email | Kapan | Ke siapa |
|---|---|---|
| Pembayaran disetujui + **lampiran kwitansi PDF** | Saat admin klik Setujui | Semua user sekolah |
| Bukti ditolak + alasan + batas upload ulang | Saat admin klik Tolak | Semua user sekolah |
| Invoice hampir kedaluwarsa | Tepat 2 hari sebelum jatuh tempo (invoice masih menunggu pembayaran) | Semua user sekolah |
| "Ada N transaksi draft di TWx yang belum bisa di-generate" | 1 Jan/Apr/Jul/Okt, hanya bila pengunci aktif dan triwulan yang baru berakhir belum punya hak | Semua user sekolah |
| Invoice menunggu verifikasi > 24 jam | Harian, selama masih ada yang menggantung (satu email berisi daftar) | Semua admin |

Perintah: `php artisan langganan:kirim-pengingat` (terjadwal 08:00 setiap hari). Opsi `--tanggal=YYYY-MM-DD` untuk uji coba. `langganan:kedaluwarsakan-invoice` terjadwal 00:10.

**Kwitansi PDF:** nomor `KWT-{tahun}-{id}` (diturunkan dari nomor invoice), memuat rincian, kode unik, jumlah dibayar, dan **terbilang** (`App\Support\Terbilang`). Sekolah mengunduhnya dari halaman invoice yang sudah lunas (`/langganan/invoice/{id}/kwitansi`). Identitas penerbit diatur lewat `LANGGANAN_PENERBIT_NAMA` dan `LANGGANAN_PENERBIT_SITUS` di `.env`.

## 10. Panel Admin v2

**Resource baru / diperluas**

- **Sekolah** (baru): nama, NPSN, alamat, kontak, tanggal daftar, jumlah user, jumlah penerimaan/pengeluaran/surat ter-generate, status hak per triwulan, total pembayaran. Filter dan ekspor CSV.
- **User** (diperluas): nama, email, status verifikasi email, cara daftar (Google/email), tanggal daftar, **terakhir login**, sekolah (nama + NPSN), ringkasan aktivitas. Untuk pelacakan, yang dicatat hanya waktu login terakhir (bukan IP) dan disebut di kebijakan privasi.
- **Invoice** (baru): daftar + bukti transfer + aksi **Setujui / Tolak** (alasan wajib).
- **Hak Triwulan** (baru): lihat, berikan manual (promo/komplimen), cabut.
- **Paket & Harga**: katalog harga (Triwulan, Tahunan).
- **Rekening**: `RekeningDonasiResource` dipakai ulang sebagai **rekening tujuan pembayaran**; label tampil menjadi "Rekening Pembayaran".
- **Banner** (baru): lihat §11.

**Dashboard admin (widget)**: total sekolah; pendaftar baru 7/30 hari; sekolah aktif bulan ini; pendapatan bulan ini dan per Tahun Anggaran; **invoice menunggu verifikasi** (dengan tombol cepat); sekolah belum selesai onboarding; sekolah dengan banyak draft belum di-generate (calon pembeli); grafik pendaftaran dan pendapatan per bulan.

**Menu admin (dikelompokkan)**: Dashboard · *Sekolah & User* (Sekolah, User) · *Keuangan* (Invoice, Hak Triwulan, Paket & Harga, Rekening) · *Konten* (Banner, Video Tutorial) · *Sistem* (Tahun Anggaran, Pengaturan Aplikasi).

## 11. Banner

Menu **Banner** di panel admin untuk mengunggah gambar yang tampil di **dashboard sekolah** dan **landing page**.

| Field | Keterangan |
|---|---|
| `judul` | Nama internal (tidak tampil ke publik) |
| `gambar_desktop` | **1600 × 400 px** (rasio 4:1). Tampil di layar ≥ 768px |
| `gambar_mobile` | **800 × 600 px** (rasio 4:3). Tampil di layar < 768px |
| `teks_alt` | Teks alternatif (aksesibilitas) |
| `link_url` | Opsional. Banner bisa diklik |
| `lokasi` | `dashboard` \| `landing` \| `keduanya` |
| `urutan` | Urutan tampil |
| `aktif` | Saklar tayang |
| `tayang_mulai`, `tayang_selesai` | Opsional. Jadwal otomatis |

Aturan upload: JPG/PNG/WebP, maksimal **1 MB** per gambar, **dimensi harus persis** sesuai ukuran di atas (divalidasi). Disimpan di `storage/app/public/banners`.

Tampilan responsif memakai `<picture>` dengan `<source media="(min-width: 768px)">` untuk gambar desktop dan `<img>` untuk gambar mobile. Bila ada lebih dari satu banner aktif pada satu lokasi, tampil sebagai slider otomatis (5 detik) dengan Alpine.js; bila tidak ada, bagian banner tidak dirender sama sekali.

## 12. Landing Page v3

- Bagian **harga** membaca katalog dari `paket_subscription`: Triwulan Rp39.000 dan Tahunan Rp129.000 (label hemat Rp27.000).
- Teks promo untuk sekolah lama: gratis sampai akhir 2026, kelonggaran sampai Januari/Februari 2027.
- Bagian **Cara Berlangganan** (4 langkah, §5) dan **FAQ** (cara bayar, kapan hak aktif, apakah data hilang bila tidak berlangganan, mengapa per triwulan).
- Bagian **donasi** diganti informasi **rekening pembayaran** (dari §10).
- Slot **banner** di bagian atas.
- Tombol CTA: Daftar / Coba. Teks "PDF/Word" diganti "Word".

## 13. iPaymu (Fase 29, opsional)

Hanya dikerjakan bila verifikasi manual mulai memakan waktu. Yang berubah hanya langkah 6–7 di §5: bukti transfer diganti **webhook** (pemberitahuan otomatis dari server iPaymu) yang memicu persetujuan yang sama; kolom `invoice.metode` = `ipaymu`. Sebelum menulis kode: baca dokumentasi API resmi dan daftar biaya iPaymu, terutama **cara verifikasi signature webhook** (bagian keamanan kritis, jangan ditebak).

## 14. Keputusan (17 November 2026)

Semua poin yang sebelumnya terbuka sudah diputuskan:

1. **Percobaan sekolah baru (2027+): 3 generate gratis** per sekolah, seumur akun (bukan per tahun). Dihitung per **transaksi yang berhasil di-generate**; Batal Generate **tidak** mengembalikan kuota (supaya tidak bisa diakali). Kuota dicek setelah hak triwulan: bila triwulan sudah dibeli, kuota percobaan tidak terpakai. Implementasi: kolom `sekolah.generate_percobaan_terpakai` (default 0), batas 3 di konfigurasi.
2. **Batas waktu invoice: 7 hari** sejak dibuat; lewat itu status `kedaluwarsa`.
3. **Pencabutan hak:** admin dapat mencabut hak triwulan lewat resource **Hak Triwulan** (status `dicabut`). Tidak ada pengembalian dana otomatis; ditangani manual di luar sistem.
4. **Perubahan tanggal transaksi untuk menghindari pembayaran:** risiko **diterima** — tanggal tercetak di surat resmi dan memengaruhi pembukuan sekolah sendiri.
