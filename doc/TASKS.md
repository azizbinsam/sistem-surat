# TASKS.md — Roadmap Development

Cara pakai: kerjakan **satu fase per sesi chat** (jangan loncat-loncat). Centang `[x]` tiap task yang selesai, lalu commit file ini ke git supaya progress kesimpen. Kalau chat terputus di tengah fase, buka chat baru pakai `CONTINUATION_PROMPT.md`.

Command detail level-per-baris untuk Fase 3 dst akan di-generate Claude **saat kita mulai fase itu** (bukan ditulis semua di awal) — supaya commandnya selalu akurat sama kondisi project kamu saat itu, bukan tebakan generik.

---

## FASE 0 — Setup Environment Lokal
**Tujuan**: Laragon + MySQL + project Laravel kosong jalan di browser.

- [ ] Install Laragon (kalau belum ada) — download dari laragon.org, pilih versi Full (sudah include PHP, MySQL, Composer, Git)
- [ ] Buka Laragon → klik kanan → **Quick app** → pilih PHP versi 8.2+ (cek dulu `php -v` di terminal Laragon)
- [ ] Buka terminal (klik tombol "Terminal" di Laragon), arahkan ke folder `www`:
  ```
  cd C:\laragon\www
  ```
- [ ] Buat project Laravel 11:
  ```
  composer create-project laravel/laravel:^11.0 sistem-surat
  cd sistem-surat
  ```
- [ ] Jalankan Laragon (klik Start All), lalu buka browser ke `http://sistem-surat.test` (Laragon auto-detect folder jadi virtual host). Kalau muncul halaman default Laravel = berhasil.
- [ ] Buat database via HeidiSQL (buka dari Laragon → Database) atau lewat terminal:
  ```
  mysql -u root -e "CREATE DATABASE sistem_surat"
  ```
- [ ] Edit file `.env`, sesuaikan:
  ```
  DB_DATABASE=sistem_surat
  DB_USERNAME=root
  DB_PASSWORD=
  ```
- [ ] Test koneksi DB:
  ```
  php artisan migrate
  ```
  Kalau tidak error = koneksi DB sukses.
- [ ] Install Git (kalau belum) & init repo:
  ```
  git init
  git add .
  git commit -m "Initial Laravel install"
  ```
- [ ] Buat repo baru di GitHub (kosongan, jangan centang "add README"), lalu hubungkan:
  ```
  git remote add origin https://github.com/USERNAME/NAMA-REPO.git
  git branch -M main
  git push -u origin main
  ```
- [ ] Buka project di VS Code:
  ```
  code .
  ```

**Checkpoint**: project Laravel kosong sudah jalan di `http://sistem-surat.test`, sudah ke-push ke GitHub.

---

## FASE 1 — Auth & Struktur Dasar

**Catatan stack terkonfirmasi**: project ini pakai **Livewire Volt** (bukan class-based klasik) — komponen ditulis functional single-file di `resources/views/livewire/pages/**.blade.php` dengan syntax `new #[Layout(...)] class extends Component { ... }`. Semua instruksi kode Livewire selanjutnya di project ini mengikuti pola Volt.

- [x] Install Livewire 3, Alpine.js, Tailwind 3
- [x] Install Laravel Breeze (basic scaffolding register/login email+password, pakai Livewire stack)
- [x] Buat tabel & model `sekolah`, `users` (custom, role admin/sekolah) — kolom `google_id` disiapkan dari awal walau belum dipakai
- [x] Flow register email+password: isi email+password → redirect ke halaman lengkapi profil sekolah (via `EnsureSekolahProfileComplete` middleware, exclude request internal `/livewire/*`)
- [x] Middleware pembeda akses admin vs sekolah
- [x] Layout dasar hitam-putih (Tailwind, palet zinc) — 1 layout admin (nanti dihandle Filament), 1 layout sekolah (custom + Pines UI dipakai bertahap sesuai kebutuhan fitur)
- [ ] *(Google OAuth ditunda ke Fase 12 — redirect URI Google tidak bisa pakai domain `.test`, baru bisa disetup pas sudah ada domain publik)*

---

## FASE 2 — Skema Database Inti
- [x] Migration untuk semua tabel di `ARCHITECTURE.md` §2 (`master_barang`, `barang_alias`, `barang_masuk`, `barang_masuk_item`, `koreksi_stok`, `pegawai`, `transaksi`, `transaksi_item` — tabel `subscriptions`/`paket_subscription` ditunda ke Fase 9)
- [x] Model + relasi Eloquent untuk semua tabel
- [x] Seeder data dummy secukupnya buat testing (1 sekolah, beberapa pegawai, beberapa barang)
- [ ] Factory untuk testing (opsional tapi direkomendasikan — ditunda, seeder manual sudah cukup untuk sekarang)

---

## FASE 3 — Master Barang & Pegawai
- [x] CRUD Master Barang (manual, Livewire) — list+search+pagination, create, edit, hapus (soft delete), proteksi tenant per sekolah_id
- [x] Import Excel Master Barang (maatwebsite/excel) — halaman upload + validasi per baris + download template
- [x] CRUD Pegawai + upload foto TTD (create, edit dengan ganti TTD + hapus file lama otomatis, proteksi tenant)
- [x] Import Excel Pegawai (bulk, tanpa TTD, mapping kategori teks→enum + validasi per baris)

---

## FASE 4 — Upload Penerimaan Barang (BPU)
- [x] Form upload Excel BPU + validasi template (per baris + cek duplikat nomor BPU)
- [x] Proses matching Nama Barang → Master Barang (`BarangMatcher` service: exact match → cek alias)
- [x] UI mapping manual untuk barang yang belum dikenal (dropdown pilih existing atau buat baru inline, simpan sebagai `barang_alias`)
- [x] Simpan ke `barang_masuk` + `barang_masuk_item` (dalam DB transaction, grouped by nomor BPU)
- [x] Halaman riwayat BPU

---

## FASE 5 — Upload Transaksi Keluar & Transpose
- [x] Form upload Excel data transaksi keluar
- [x] Logic transpose: grouping by Nomor Referensi → jadi draft `transaksi` + `transaksi_item`
- [x] Matching Nama Barang (reuse `BarangMatcher` dari Fase 4)
- [x] Halaman daftar draft transaksi
- [x] Fitur mapping "pihak yang meminta" via ajax (`wire:model.live` + magic method `updated()`, auto-save on change)

---

## FASE 6 — Modul Persediaan
- [x] Implementasi query hitung sisa stok (`PersediaanService`: totalMasuk, totalKoreksi, totalKeluar dengan opsi "sebelum transaksi tertentu")
- [x] Halaman ringkasan stok per kode barang
- [x] Halaman history ledger (masuk/keluar/koreksi) per kode barang, dengan saldo berjalan
- [x] Form Koreksi Stok (+/- dengan alasan wajib, disimpan sebagai signed integer)
- [x] Tampilkan sisa stok real-time di preview transaksi sebelum generate surat (dengan warning kalau stok tidak cukup)

---

## FASE 7 — Generate Surat (PDF & Word)

**Catatan teknis fidelity** (penting, baca sebelum mulai):
- **Word**: pakai PHPWord `TemplateProcessor` dengan basis file `7__NPB_SPB_SPPB.docx` asli (bukan bangun dari nol) → siapkan versi template dengan placeholder (`${nomor_surat}`, dst) + teknik clone-row untuk tabel item dinamis. Hasilnya sangat mendekati identik karena formatting ikut file asli.
- **PDF**: dompdf render dari HTML/CSS custom yang didesain semirip mungkin ke docx asli — visual mirip tapi bukan pixel-perfect (font-rendering engine beda). Kalau butuh PDF identik 1:1, itu perlu convert Word→PDF pakai LibreOffice headless, yang butuh VPS (shared hosting kemungkinan tidak support) — dijadikan opsi v2, bukan wajib v1.

- [x] Siapkan file docx template (placeholder version) dari `7__NPB_SPB_SPPB.docx` — dikonfirmasi ulang struktur asli (kop, jabatan resmi SPPB "Kuasa Pengguna Barang", kode format II.I.6/7/8, prefix nomor 000.2.3.1), ditambah placeholder logo kabupaten/sekolah & TTD opsional, macro di-uniquekan per tabel buat cloneRow
- [x] Setup barryvdh/laravel-dompdf & phpoffice/phpword
- [x] Bikin logic penomoran surat otomatis (`NomorSuratService`: NPB global increment, SPB/SPPB turunan dari nomor NPB)
- [x] Template Blade + CSS untuk PDF (perlu revisi lanjutan — dicatat sebagai item pending, bukan blocker)
- [x] Template Word (PHPWord TemplateProcessor + clone-row logic untuk tabel item, 3 tabel dengan macro unik)
- [x] Generate per transaksi (download 1 bundel, Word atau PDF, nomor surat digenerate sekali & disimpan permanen)
- [x] Bulk generate (checkbox banyak transaksi → download zip, Word atau PDF)

**Item pending untuk disempurnakan nanti:** hasil PDF belum sesuai (perlu revisi CSS/layout dompdf).

---

## FASE 8 — Panel Admin (Filament 3)
- [x] Install Filament 3, akses dibatasi role admin via `FilamentUser` contract
- [x] Resource: Manage User (+promote admin)
- [x] Resource: Manage Subscription (perpanjang/hold/aktifkan manual, logic perpanjang mempertahankan sisa hari kalau belum expired)
- [x] Resource: Manage Paket Harga
- [x] *(Ditambahkan lebih awal dari rencana: migration & model `paket_subscription` + `subscriptions`, awalnya dijadwalkan Fase 9 — integrasi payment gateway tetap di Fase 9)*

---

## FASE 9 — Subscription & Payment (Fersaku)

**Status: DITUNDA** — web Fersaku (termasuk halaman dokumentasi API) lagi bermasalah pas sesi ini. Item di bawah **di-skip dulu**, lanjut ke Fase 10 dulu, balik lagi ke sini begitu Fersaku bisa diakses lagi.

- [ ] Cek ulang dokumentasi API Fersaku (auth header, format request create payment, format & cara verifikasi signature webhook — **jangan asal tebak**, ini bagian keamanan kritis)
- [ ] Setup Fersaku (sandbox/API key test dulu)
- [ ] Flow beli/perpanjang paket → generate halaman pembayaran Fersaku → redirect
- [ ] Webhook Fersaku → verifikasi signature → update status subscription otomatis
- [ ] Middleware cek subscription aktif (block akses fitur kalau expired)
- [ ] Halaman Info Subscription di panel sekolah

---

## FASE 10 — Landing Page & Pengaturan Sekolah
- [x] Landing page (hero, fitur, harga dinamis dari paket subscription, CTA daftar) — Blade view biasa, bukan Livewire (karena landing page statis tidak butuh reaktivitas)
- [x] Halaman Pengaturan Sekolah (logo sekolah + kabupaten, identitas, kop surat, kontak, ganti password)

---

## FASE 11 — Testing & Polish
- [x] Test alur end-to-end: upload BPU → upload transaksi → mapping → generate surat (ditest mandiri, berjalan normal)
- [x] Cek edge case: barang belum pernah ada BPU tapi diminta (harus ditolak/warning)
- [x] Cek edge case: Excel format salah (kolom hilang, dsb) — pesan error jelas
- [x] Responsive check (mobile, karena panel sekolah kemungkinan diakses admin sekolah dari HP juga)

---

## FASE 12 — Deployment ke Shared Hosting

**Status: DITUNDA** — push ke GitHub dulu (dikerjakan sekarang), lanjut Fase 9 (Fersaku) kalau sudah bisa diakses, baru deploy.
- [ ] Siapkan `.env` produksi (DB, APP_URL, Fersaku production key)
- [ ] Build asset (`npm run build`)
- [ ] Upload via Git deploy atau manual zip ke cPanel
- [ ] Setup database di cPanel (phpMyAdmin), import struktur
- [ ] Jalankan migration produksi (`php artisan migrate --force`)
- [ ] Setup document root kalau shared hosting (arahkan ke folder `public`, atau pakai trik `.htaccess` kalau nggak bisa ubah document root)
- [ ] Setup queue worker (kalau shared hosting support, atau pakai `sync` driver kalau tidak ada akses cron/worker)
- [ ] Setup SSL (biasanya sudah include di cPanel — AutoSSL)
- [ ] Install Laravel Socialite + setup Google OAuth (buat credentials di Google Cloud Console dengan redirect URI domain produksi, misal `https://domainkamu.com/auth/google/callback`)
- [ ] Tambah toggle "Login dengan Google" di halaman login (logic: kalau email sudah terdaftar via email+password, link ke akun yang sama — bukan bikin akun duplikat)

---

## FASE 13 — Styling Ulang (Pines UI)

Semua fitur udah jalan fungsional, sekarang polish visual — pakai pola Pines UI (Alpine.js + Tailwind, copy-paste component style), tema tetap zinc.

- [x] Layout shell dashboard: sidebar gelap dengan grouping menu, responsive mobile overlay, dashboard diisi kartu statistik data asli (bukan placeholder Breeze)
- [x] Redesign halaman auth (login, register) — split-screen, card rounded, aksen emerald
- [x] Redesign landing page — hero gelap, fitur dengan icon, pricing card, CTA section
- [ ] Polish halaman CRUD satu-satu (table, form, empty state) — dikerjain bertahap sesuai prioritas

---

## FASE 14 — Revisi Manajemen Stok & Transaksi (hasil testing mandiri)

Ditemukan pas testing Fase 11: modul Persediaan/Transaksi masih kurang lengkap buat operasional harian (nggak ada edit/delete, bug perhitungan ledger, dll). Total 11 poin revisi.

### 14.1 — Fix bug ledger (prioritas tertinggi, mendasari semua yang lain)
- [ ] Refactor `PersediaanService`: cutoff tanggal konsisten di totalMasuk/totalKoreksi/totalKeluar (bukan cuma totalKeluar doang)
- [ ] Tie-breaker pakai `created_at`, bukan `id` (biar valid lintas tabel)
- [ ] Method baru: `sisaSaatIni()` pakai cutoff `now()`, terpisah jelas dari `sisaSebelumTransaksi()`

### 14.2 — Master Barang: field tambahan
- [ ] Tambah kolom `spesifikasi_default` (migration + form create/edit + template excel §7.1)

### 14.3 — Penerimaan Barang (BPU): CRUD lengkap
- [ ] Form Tambah manual (dynamic multi-item, tanggal bebas)
- [ ] Form Edit
- [ ] Delete + warning kalau berdampak ke transaksi yang udah pakai barang itu (bikin minus)

### 14.4 — Transaksi Keluar: CRUD lengkap + fitur baru
- [ ] Form Tambah manual (dynamic multi-item, tanggal bebas, spesifikasi opsional, combobox search pihak peminta)
- [ ] Form Edit (nomor surat tidak berubah kalau statusnya udah "selesai")
- [ ] Delete + validasi
- [ ] Tab filter Draft / Selesai / Semua di halaman daftar
- [ ] Action per status: Draft (mapping+generate), Selesai (edit/delete/download ulang)
- [ ] Warning stok tidak cukup (create/edit manual maupun mapping draft) + tombol shortcut ke edit BPU terkait
- [ ] Combobox search buat dropdown pihak peminta (ganti `<select>` polos)

### 14.5 — Import Excel Transaksi Keluar: kolom & logic baru
- [ ] Kolom "Spesifikasi" jadi opsional di reader/validasi
- [ ] Kolom baru: Nama Peminta, Jabatan Peminta (opsional) — auto-mapping ke Pegawai (kombinasi nama+jabatan, exact match)
- [ ] Notifikasi transaksi mana yang gagal auto-mapping
- [ ] Validasi: warning kalau Nama Peminta beda-beda dalam 1 Nomor Referensi
- [ ] Kolom baru: Nomor NPB (opsional) — override auto-generate buat data historis
- [ ] Update template excel §7.3 (download template)

### 14.6 — Penomoran: hybrid auto-generate + override
- [ ] `NomorSuratService`: skip generate kalau `nomor_npb` udah diisi manual dari excel
- [ ] Field "Nomor Urut Terakhir" jadi editable di halaman Pengaturan Sekolah

### 14.7 — Koreksi Stok: reposisi (dokumentasi/UX, bukan perubahan skema)
- [ ] Update helper text di form Koreksi Stok, perjelas ini khusus kasus fisik (rusak/hilang/opname), bukan buat benerin salah input lagi

Detail command tiap fase (composer require spesifik, artisan make:model, dst) akan saya tulis lengkap pas kita mulai fase itu.
