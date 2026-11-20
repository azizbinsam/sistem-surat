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

**Menu (dikelompokkan):** Dashboard · *Sekolah & User* (Sekolah, User) · *Keuangan* (Invoice, Hak Triwulan, Paket & Harga, Rekening Pembayaran) · *Konten* (Video Tutorial; Banner menyusul di Fase 26) · *Sistem* (Tahun Anggaran, Pengaturan Aplikasi).

**Resource**

- **Sekolah** (baru, hanya lihat — tanpa tambah/ubah/hapus): nama, NPSN, kota, kontak, jumlah user, jumlah pengeluaran, sudah di-generate, draft, triwulan aktif, kuota percobaan (x/3), total pembayaran, terakhir aktif, tanggal daftar. Filter: belum pernah generate, draft menumpuk (5+), punya triwulan aktif, kuota habis, tidak aktif 30 hari. Halaman detail memuat daftar user dan hak triwulan. **Ekspor CSV.** Catatan: karena tidak ada user sekolah yang login di panel admin, hitungan transaksi mencakup semua Tahun Anggaran.
- **User** (diperluas): nama, email, role, sekolah (+ NPSN), **daftar via** (Google/Email), email terverifikasi, **terakhir login**, tanggal daftar. Filter: role, email terverifikasi, belum melengkapi profil sekolah, belum pernah login, tidak aktif 30 hari. **Ekspor CSV.**
- **Invoice**: Setujui / Tolak (alasan wajib) / Lihat bukti; angka di menu = jumlah menunggu verifikasi.
- **Hak Triwulan** (baru): daftar semua hak; aksi **Beri Hak** (sumber `manual` atau `gratis`, batas waktu opsional, catatan), **Cabut**, **Aktifkan kembali**. Hak berbayar aktif tidak bisa ditimpa. Hak yang sudah ada (dicabut/gratis kedaluwarsa) diperbarui, bukan dibuat ganda.
- **Paket & Harga**: ubah harga Triwulan dan Tahunan.
- **Rekening Pembayaran** (sebelumnya "Rekening Donasi"): rekening tujuan transfer yang tampil di halaman invoice sekolah.

**Pelacakan user:** hanya waktu login terakhir (`users.last_login_at`, diisi oleh event Login Laravel — termasuk login Google). Tidak mencatat IP maupun perangkat; sebutkan di kebijakan privasi.

**Ekspor CSV:** berkas diawali BOM UTF-8 (agar Excel membaca karakter Indonesia dengan benar), dan sel yang diawali `=`, `+`, `-`, `@` diberi tanda petik di depan supaya tidak dijalankan sebagai rumus (nama sekolah/user berasal dari input pengguna).

**Dashboard admin (widget):**

| Widget | Isi |
|---|---|
| Statistik | Total sekolah (+7 hari), pendaftar baru 30 hari, sekolah aktif bulan ini (minimal 1 user login), pendapatan bulan ini dan tahun ini, invoice menunggu verifikasi, user belum melengkapi profil |
| Invoice Menunggu Verifikasi | Antrean kerja dengan tombol Lihat Bukti / Setujui / Tolak (aksi sama dengan menu Invoice) |
| Grafik | Pendaftaran sekolah per bulan dan pendapatan per bulan (12 bulan terakhir) |
| Calon Pembeli | Sekolah yang punya transaksi draft, diurutkan dari draft terbanyak, beserta triwulan aktif, kuota, dan terakhir aktif |
| Belum Menyelesaikan Onboarding | User yang sudah mendaftar tapi belum mengisi data sekolah |

## 11. Banner

Menu **Banner** (grup Konten) di panel admin untuk mengunggah gambar yang tampil di **dashboard sekolah** dan **landing page**.

| Field | Keterangan |
|---|---|
| `judul` | Nama internal (tidak tampil ke publik) |
| `gambar_desktop` | **1600 × 400 px** (rasio 4:1). Tampil di layar ≥ 768px |
| `gambar_mobile` | **800 × 600 px** (rasio 4:3). Tampil di layar < 768px |
| `teks_alt` | Teks alternatif (aksesibilitas; tampil bila gambar gagal dimuat) |
| `link_url` | Opsional. Hanya `http://`, `https://`, atau path relatif satu garis miring (`/register`) |
| `lokasi` | `dashboard` \| `landing` \| `keduanya` |
| `urutan` | Angka kecil tampil lebih dulu; bisa diatur dengan tarik-lepas di tabel |
| `aktif` | Saklar tayang |
| `tayang_mulai`, `tayang_selesai` | Opsional. Jadwal otomatis |

**Aturan upload:** JPG/PNG/WebP (SVG sengaja ditolak karena bisa menyisipkan skrip), maksimal **1 MB** per gambar, dan **dimensi harus persis** sesuai ukuran di atas (divalidasi). Disimpan di `storage/app/public/banners` (perlu `php artisan storage:link`). Gambar lama otomatis dihapus dari disk saat diganti atau saat banner dihapus.

**Siapa yang tayang:** banner aktif, lokasinya cocok (atau "keduanya"), dan sedang dalam jadwal tayang (bila diisi), berurutan menurut `urutan` lalu `id`. Tabel admin menampilkan status **Tayang / Terjadwal / Berakhir / Nonaktif**.

**Tampilan (komponen `<x-banner-slider lokasi="dashboard|landing" />`):**

- Responsif lewat `<picture>`: gambar desktop dipakai di layar ≥ 768px (`<source media="(min-width: 768px)">`), gambar ponsel di bawahnya. Kotak slider berubah rasio (4:3 di ponsel, 4:1 di desktop) agar gambar selalu pas.
- **Satu banner** = gambar saja. **Lebih dari satu** = **slider otomatis** (ganti tiap 5 detik, ada titik navigasi). Berhenti saat disorot mouse atau difokus, dan tidak berjalan otomatis bagi pengguna yang meminta gerakan dikurangi (`prefers-reduced-motion`).
- Ditulis dengan JavaScript biasa (tanpa Alpine/library), sehingga jalan di landing page (tanpa Livewire) maupun dashboard (dengan `wire:navigate`).
- Tidak ada banner tayang = bagian banner tidak dirender sama sekali.
- **Keamanan:** tautan diperiksa dua kali (aturan validasi di form, dan `Banner::linkAman()` saat tampil) sehingga `javascript:` dan sejenisnya tidak pernah menjadi `href`; teks alt di-escape; tautan eksternal memakai `rel="noopener noreferrer"` dan dibuka di tab baru.

## 12. Landing Page v3

Dikendalikan oleh `LandingController` (sebelumnya closure di `routes/web.php`).

- **Harga dinamis:** bagian **Harga** membaca katalog `paket_langganan` (menu admin *Paket & Harga*), jadi mengubah harga langsung tampil tanpa deploy. Menampilkan kartu Triwulan dan Tahunan, label **Hemat Rp…** (4 × harga triwulan − harga tahunan; disembunyikan bila tidak ada selisih), dan harga per triwulan untuk paket Tahunan. Bila katalog kosong, bagian Harga dan Cara Berlangganan disembunyikan dan halaman tetap tampil normal.
- **Promo sekolah lama:** blok promo, badge di hero, dan satu butir FAQ ("Saya sudah terdaftar. Apakah tetap gratis?") dikendalikan `config('langganan.promo')`: `tampil`, `gratis_sampai` ("31 Desember 2026"), `tw4_sampai` ("28 Februari 2027"). Setelah masa promo selesai, set `tampil` ke `false`; badge hero otomatis berganti menjadi "Coba gratis: 3 generate surat tanpa biaya".
- **Cara Berlangganan** (4 langkah, §5) dan **FAQ** (7 pertanyaan, memakai `<details>` bawaan HTML tanpa JavaScript).
- **Hero & fitur:** klaim "100% gratis", "tanpa biaya berlangganan", dan "Gratis, Didukung Donasi" diganti; teks "Word & PDF" menjadi "Word"; navbar punya tautan ke Fitur, Harga, dan FAQ.
- **Slot banner** tetap di bawah navbar (§11).
- **Keputusan:** nomor rekening **tidak** ditampilkan di landing page (rencana awal menampilkannya). Transfer tanpa invoice tidak membawa kode unik sehingga sulit dicocokkan; rekening tujuan cukup tampil di halaman invoice setelah sekolah membuat pesanan. Bagian donasi di landing dihapus. Blok "Dukung Kami" di dashboard sekolah belum diubah.

## 13. iPaymu (Fase 29, opsional)

Hanya dikerjakan bila verifikasi manual mulai memakan waktu. Yang berubah hanya langkah 6–7 di §5: bukti transfer diganti **webhook** (pemberitahuan otomatis dari server iPaymu) yang memicu persetujuan yang sama; kolom `invoice.metode` = `ipaymu`. Sebelum menulis kode: baca dokumentasi API resmi dan daftar biaya iPaymu, terutama **cara verifikasi signature webhook** (bagian keamanan kritis, jangan ditebak).

## 14. Keputusan (17 November 2026)

Semua poin yang sebelumnya terbuka sudah diputuskan:

1. **Percobaan sekolah baru (2027+): 3 generate gratis** per sekolah, seumur akun (bukan per tahun). Dihitung per **transaksi yang berhasil di-generate**; Batal Generate **tidak** mengembalikan kuota (supaya tidak bisa diakali). Kuota dicek setelah hak triwulan: bila triwulan sudah dibeli, kuota percobaan tidak terpakai. Implementasi: kolom `sekolah.generate_percobaan_terpakai` (default 0), batas 3 di konfigurasi.
2. **Batas waktu invoice: 7 hari** sejak dibuat; lewat itu status `kedaluwarsa`.
3. **Pencabutan hak:** admin dapat mencabut hak triwulan lewat resource **Hak Triwulan** (status `dicabut`). Tidak ada pengembalian dana otomatis; ditangani manual di luar sistem.
4. **Perubahan tanggal transaksi untuk menghindari pembayaran:** risiko **diterima** — tanggal tercetak di surat resmi dan memengaruhi pembukuan sekolah sendiri.
