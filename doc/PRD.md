# PRD — Sistem Generate Surat NPB/SPB/SPPB (SaaS)

Status: v1.0 — hasil diskusi awal, siap masuk fase development
Terakhir update: 21 Agustus 2026
Developer: Aziz (Delix Studio)

---

## 1. Latar Belakang & Tujuan

Sekolah (SDN) rutin bikin 3 surat berurutan tiap ada permintaan/pengeluaran barang persediaan:
**NPB (Nota Permintaan Barang) → SPB (Surat Permintaan Barang) → SPPB (Surat Perintah Penyaluran Barang)**.

Prosesnya saat ini manual (isi Word/Excel satu-satu). Tujuan produk ini: sekolah tinggal **upload data Excel**, sistem otomatis:
1. Transpose data mentah jadi transaksi terstruktur
2. Hitung sisa persediaan otomatis (ledger masuk-keluar)
3. Generate 3 surat sekaligus (PDF/Word), siap cetak & tanda tangan

Model bisnis: **SaaS subscription** per sekolah (bulanan/tahunan), dijual oleh Delix Studio.

---

## 2. Tech Stack

| Layer | Pilihan | Catatan |
|---|---|---|
| Backend | Laravel 11 | |
| Frontend interaktif | Livewire 3 + Alpine.js | |
| Styling | Tailwind 3 | Tema hitam-putih, simpel |
| Admin panel | Filament 3 | Manage user, subscription, paket harga |
| Panel sekolah | Custom Livewire + Pines UI | Fleksibel untuk UX spesifik (mapping ajax, generate surat) |
| Database | MySQL | Lokal: Laragon. Produksi: shared hosting (cPanel) |
| Auth | Laravel Breeze/Fortify (email+password) + Laravel Socialite (Google OAuth) | Dua opsi login, sekolah bebas pilih |
| Payment | Fersaku (QRIS) | Platform pembuatan halaman pembayaran QRIS Indonesia — lebih simpel dari Midtrans (1 endpoint API + webhook), fee 1%+Rp500 s/d 3% per transaksi tergantung tier. Detail teknis integrasi (auth header, format signature webhook) menyusul saat dokumentasi API-nya bisa diakses lagi |
| Import Excel | maatwebsite/excel (PhpSpreadsheet) | |
| Export PDF | barryvdh/laravel-dompdf | |
| Export Word | phpoffice/phpword | |
| Version control | Git + GitHub | |
| Deployment | Shared hosting (cPanel) | |

---

## 3. User Roles

- **Admin** (internal Delix Studio) — kelola platform & subscription semua sekolah
- **Sekolah** (subscriber) — 1 akun = 1 sekolah, kelola data & generate surat sendiri

---

## 4. Fitur — Admin (Filament 3)

- **Manage User**: list, edit, promote jadi admin
- **Manage Subscription**: lihat status tiap sekolah, extend/hold/cancel manual
- **Manage Paket Harga**: CRUD paket (nama, harga, durasi — misal Bulanan/Tahunan)
- **Manage Status Subscription**: perpanjang, hold (suspend akses), expired (otomatis dari sistem atau manual override)

---

## 5. Fitur — Sekolah

### 5.1 Dashboard
Ringkasan: jumlah surat digenerate bulan ini, status subscription, alert stok barang tertentu (opsional v2).

### 5.2 Master Barang *(setup awal, opsional)*
- CRUD manual: Kode Barang, Nama, Kategori, Satuan Default
- Import bulk via Excel (opsional, lihat §7.1)
- Alias otomatis kesimpen tiap kali sekolah mapping "Nama Barang" baru dari upload BPU/transaksi ke kode master yang sudah ada

### 5.3 Penerimaan Barang (BPU)
- **CRUD lengkap**: tambah manual (form, boleh isi tanggal kapan saja — bukan cuma hari ini), edit, delete — semua langsung mempengaruhi ledger stok secara real-time (bukan angka statis, ledger dihitung ulang tiap kali dari data yang ada)
- **Tambah manual**: 1 form bisa isi banyak item sekaligus (dynamic row), field: Nomor BPU, Tanggal, lalu per item: Barang (pilih dari Master Barang), Spesifikasi, Satuan, Jumlah
- **Upload Excel** (template §7.2) — alur sama seperti sebelumnya: 1 Nomor BPU = 1 transaksi penerimaan, auto-match Nama Barang + Spesifikasi → Kode Barang master, mapping manual + alias kalau belum dikenal
- **Delete/Edit warning**: kalau mengurangi/menghapus BPU bikin ada transaksi keluar yang jadi "minus stok" (transaksi itu udah terlanjur pakai barang yang sekarang berkurang), sistem kasih peringatan jelas sebelum konfirmasi

### 5.4 Transaksi Keluar (Permintaan Barang)
- **CRUD lengkap**: tambah manual, edit, delete — sama seperti BPU, tanggal bebas, langsung pengaruh ke ledger
- **Tambah manual**: form dinamis (Nomor Referensi, Tanggal, pihak peminta via combobox search, item-item: Barang, Spesifikasi *(opsional — fallback ke nama barang kalau kosong)*, Jumlah, Satuan, Keperluan)
- **Upload Excel** (template §7.3, kolom diperbarui — lihat §7.3):
  - Sistem transpose: grouping baris berdasarkan **Nomor Referensi** → jadi transaksi (1 = 1 bundel surat NPB+SPB+SPPB)
  - Mapping barang yang belum dikenal (sama seperti BPU)
  - **Auto-mapping pihak peminta**: kalau kolom Nama + Jabatan Peminta di Excel cocok persis (kombinasi keduanya) dengan data Pegawai, otomatis ke-assign. Kalau nggak cocok, dikosongkan + sistem tampilkan notifikasi jelas ("N transaksi belum bisa dipetakan otomatis: No. Ref ...") — bisa dilengkapi manual
  - **Nomor NPB opsional** di Excel: kalau diisi (buat import data historis yang udah py nomor asli dari kertas), sistem pakai nomor itu apa adanya, skip auto-generate. Kalau kosong, tetap auto-generate seperti biasa (lihat §8.1)
  - Validasi: kalau dalam 1 Nomor Referensi ada Nama Peminta yang beda-beda antar baris, sistem warning (indikasi salah ketik)
- **Halaman daftar transaksi** — ada **tab filter: Draft / Selesai / Semua**:
  - **Draft**: mapping pihak yang meminta (combobox search, ajax auto-save), preview sisa persediaan real-time per item, tombol Generate (per transaksi atau bulk)
  - **Selesai**: tombol Edit (update data, nomor surat TIDAK berubah karena udah "resmi" terbit), Delete, Download Ulang
- **Warning stok tidak cukup**: baik pas create/edit manual maupun pas mapping dari draft, kalau jumlah diminta > sisa stok, tampilkan warning jelas + tombol langsung ke halaman edit BPU barang terkait

### 5.5 Persediaan / Stok
- Halaman ringkasan sisa stok per kode barang (Total Masuk + Total Koreksi − Total Keluar, dihitung konsisten berdasarkan cutoff tanggal — lihat §8.2)
- History ledger per kode barang (tabel: tanggal, jenis (masuk/keluar/koreksi), referensi (no. BPU/no. transaksi), jumlah, saldo berjalan)
- **Koreksi Stok**: form tambah entri koreksi (+/-), wajib isi alasan. **Scope dipersempit** — sekarang khusus buat kasus selisih fisik nyata (barang rusak, hilang, hasil stok opname) yang nggak ada dokumen sumbernya (bukan BPU maupun transaksi keluar). Kasus salah input data sekarang diperbaiki langsung lewat Edit di BPU/Transaksi Keluar (§5.3, §5.4), bukan lewat entri koreksi lagi. Tetap bisa diakses semua akun sekolah.

### 5.6 CRUD Pegawai
- Manual CRUD: Nama, Jabatan, NIP, Kategori (Kepala Sekolah / Pengurus Barang Pembantu / Guru / Tendik), foto Tanda Tangan (upload gambar)
- Bulk import Excel (template §7.4) untuk data teks (Nama, Jabatan, NIP, Kategori) — tanda tangan tetap upload manual per pegawai setelah import

### 5.7 Pengaturan Sekolah

**Identitas umum**
- Logo sekolah, logo kabupaten
- Kontak WA, email
- Update password

**Kop Surat** *(dipakai persis untuk cetak header semua surat, sesuai referensi docx)*
- Kode Sekolah (contoh: `SDN3RKST`) — dipakai di format nomor surat
- Kode Klasifikasi Surat (contoh: `000.2.3.1`) — prefix konstan di semua nomor surat
- Nama Pemerintah Daerah (contoh: "PEMERINTAH KABUPATEN LEBAK")
- Nama Dinas (contoh: "DINAS PENDIDIKAN")
- Nama Korwil/UPTD (contoh: "KORWIL SATUAN PENDIDIKAN") — opsional, kosongkan kalau daerah lain tidak pakai struktur ini
- Nama Sekolah (contoh: "SEKOLAH DASAR NEGERI 3 RANGKASBITUNG TIMUR")
- Alamat Sekolah (dicetak di bawah nama sekolah di kop)
- Tempat (dipakai di baris tanggal tanda tangan, misal "Rangkasbitung" — beda dari alamat lengkap)

**Jabatan Resmi Penandatangan SPPB** *(khusus SPPB, label jabatan yang dicetak bukan jabatan sehari-hari Kepala Sekolah — melainkan sebutan resmi jabatannya dalam konteks pengelolaan barang)*
- Jabatan Resmi Penandatangan SPPB (contoh: "Kuasa Pengguna Barang" — defaultnya ini, tapi bisa disesuaikan per sekolah/daerah)

**Penomoran Surat**
- Nomor Urut Terakhir — editable, buat sekolah yang baru pindah dari pencatatan manual dan mau lanjut nomor dari yang terakhir dipakai di kertas (misal isi manual jadi 44, transaksi baru berikutnya otomatis mulai dari nomor 45)

**Kode Format Surat** *(label pojok kanan atas tiap jenis surat, mengikuti regulasi daerah masing-masing — dikonfirmasi ada di file `7__NPB_SPB_SPPB.docx` sebagai text box terpisah)*
- Format Kode NPB (contoh: "FORMAT II.I.6")
- Format Kode SPB (contoh: "FORMAT II.I.7")
- Format Kode SPPB (contoh: "FORMAT II.I.8")

### 5.8 Info Subscription
- Status aktif/expired, tanggal berakhir, riwayat pembayaran, tombol perpanjang (redirect ke halaman pembayaran Fersaku)

---

## 6. Struktur Dokumen Surat (per bundel transaksi)

### 6.0 Kop Surat (sama persis di ketiga jenis surat, kode format beda per jenis)
Rata tengah, urutan dari atas — dikonfirmasi dari struktur asli `7__NPB_SPB_SPPB.docx` (label kode format di pojok kanan atas ternyata ada, tersimpan sebagai text box terpisah di file docx-nya, bukan paragraf biasa):
```
[Format Kode surat ini]                    ← pojok kanan atas, dari Pengaturan §5.7
[Nama Pemerintah Daerah]
[Nama Dinas]
[Nama Korwil/UPTD]                         ← skip kalau kosong
[NAMA SEKOLAH]
[Alamat Sekolah]
```
Semua field diambil dari Pengaturan Sekolah (§5.7), bukan hardcode — supaya template ini reusable untuk sekolah/kabupaten lain.

### 6.0.1 Jabatan Penandatangan (per jenis surat)
Dikonfirmasi dari struktur asli, jabatan yang dicetak di blok tanda tangan **tidak selalu sama** dengan field `jabatan` pegawai apa adanya:
- **NPB**: jabatan = `pegawai.jabatan` milik Pihak yang Meminta (dinamis, sesuai siapa yang mengajukan)
- **SPB**: jabatan = `pegawai.jabatan` milik Pengurus Barang Pembantu (tetap, contoh: "Pengurus Barang Pembantu")
- **SPPB**: jabatan = **bukan** `pegawai.jabatan` Kepala Sekolah, melainkan field terpisah **"Jabatan Resmi Penandatangan SPPB"** dari Pengaturan Sekolah (§5.7, default "Kuasa Pengguna Barang") — walau namanya sama dengan Kepala Sekolah, sebutan jabatannya beda karena konteks pengelolaan barang milik negara

### 6.1 Nota Permintaan Barang (NPB)
- Nomor surat (auto-generate, lihat §8.1)
- Pihak yang meminta (nama+jabatan, dari mapping)
- Tabel item: No urut, Spesifikasi Nama Barang, Jumlah, Satuan, Keperluan, Ket. (kosong)
- Tempat, Tanggal (dari kolom "Tanggal NPB" hasil transpose)
- Blok tanda tangan: Jabatan, TTD (image), Nama, NIP (pihak peminta)

### 6.2 Surat Permintaan Barang (SPB)
- Nomor surat (turunan dari nomor NPB, lihat §8.1)
- Referensi: Nomor & Tanggal NPB, Pihak yang meminta
- Tabel item: No, Kode Barang, Nama Barang, Spesifikasi, Pengajuan Permintaan (Jml+Satuan), **Informasi Sisa Barang Persediaan** (Jml+Satuan, dari ledger — §8.2), Usulan Pengajuan Persetujuan (Jml+Satuan), Keperluan, Ket.
- Tempat, Tanggal
- Blok tanda tangan: Pengurus Barang Pembantu (tetap di semua SPB sekolah tsb)

### 6.3 Surat Perintah Penyaluran Barang (SPPB)
- Nomor surat (turunan dari nomor SPB)
- Referensi: Nomor & Tanggal SPB
- Tabel item: No, Kode Barang, Nama Barang, Spesifikasi, Satuan, Persetujuan Pengeluaran Barang, Ket.
- Tempat, Tanggal
- Blok tanda tangan: Kepala Sekolah (tetap di semua SPPB sekolah tsb)

---

## 7. Template Excel

### 7.1 Master Barang *(opsional)*
| Kolom | Wajib | Keterangan |
|---|---|---|
| Kode Barang | Ya | Unik |
| Nama Barang | Ya | |
| Kategori | Tidak | |
| Satuan Default | Ya | |
| Spesifikasi Default | Tidak | Auto-suggest saat isi excel transaksi keluar |

### 7.2 Penerimaan Barang (BPU)
| Kolom | Wajib | Keterangan |
|---|---|---|
| Tanggal | Ya | |
| Nomor BPU | Ya | Kunci grouping 1 transaksi penerimaan |
| Nama Barang | Ya | |
| Spesifikasi | Ya | |
| Satuan | Ya | |
| Jumlah | Ya | |

### 7.3 Data Transaksi Keluar
| Kolom | Wajib | Keterangan |
|---|---|---|
| Tanggal | Ya | |
| Nomor Referensi | Ya | Kunci grouping — sama nomor = 1 bundel surat |
| Nama Barang | Ya | |
| Spesifikasi | **Tidak** | Fallback ke Nama Barang master kalau kosong |
| Jumlah | Ya | |
| Satuan | Ya | |
| Keperluan | Ya | |
| Nama Peminta | Tidak | Buat auto-mapping pihak yang meminta (kombinasi dengan Jabatan Peminta) |
| Jabatan Peminta | Tidak | Wajib diisi bareng Nama Peminta kalau mau auto-mapping jalan |
| Nomor NPB | Tidak | Khusus import data historis yang udah punya nomor surat asli dari kertas — kalau diisi, sistem pakai nomor ini apa adanya (skip auto-generate) |

### 7.4 Data Pegawai
| Kolom | Wajib | Keterangan |
|---|---|---|
| Nama | Ya | |
| Jabatan | Ya | |
| NIP | Ya | |
| Kategori Pegawai | Ya | Kepala Sekolah / Pengurus Barang Pembantu / Guru / Tendik |

Semua template ini disediakan sebagai file `.xlsx` yang bisa di-download langsung dari tombol di masing-masing halaman upload.

---

## 8. Business Rules Kritis

### 8.1 Penomoran Surat — hybrid (auto-generate atau override manual)
- **Default (auto-generate)**: sama seperti sebelumnya
  - **NPB**: `{kode_klasifikasi_surat}/{urut 4-digit}/NPB-{KODE_SEKOLAH}/{bulan-romawi}/{tahun}`
    contoh: `000.2.3.1/0043/NPB-SDN3RKST/IV/2026`
  - **SPB**: sama seperti NPB, tapi nomor urut ditambah `.1`
  - **SPPB**: sama seperti NPB, tapi nomor urut ditambah `.2`
  - `{kode_klasifikasi_surat}` dan `{KODE_SEKOLAH}` dari Pengaturan Sekolah — bukan hardcode
  - **Global increment** — nomor urut TIDAK reset tiap bulan, lanjut terus dari `nomor_urut_terakhir` di Pengaturan Sekolah (§5.7 — bisa diedit manual buat lanjutin dari pencatatan kertas lama)
  - Bulan romawi & tahun mengikuti `tanggal_npb` transaksi tersebut (bukan tanggal surat digenerate)
- **Override via Excel (§7.3, kolom "Nomor NPB")**: khusus import data historis yang udah punya nomor asli dari kertas — kalau kolom ini diisi, sistem pakai nomor itu apa adanya untuk NPB, dan tetap turunkan SPB/SPPB dari situ (`.1`/`.2`) — **skip** auto-generate. Nomor historis ini tidak mempengaruhi/menaikkan counter `nomor_urut_terakhir` (biar nggak bentrok sama urutan auto-generate ke depannya)

### 8.2 Ledger Persediaan (Sisa Stok)
```
Sisa Persediaan (kode barang X, per tanggal cutoff T) =
  SUM(jumlah masuk dari BPU, kode X, tanggal <= T)
  + SUM(jumlah koreksi, kode X, tanggal <= T)
  − SUM(jumlah keluar dari transaksi, kode X, tanggal <= T, tapi TIDAK termasuk transaksi T sendiri)
```
- **Penting**: ketiga komponen (masuk, koreksi, keluar) HARUS pakai cutoff tanggal yang konsisten — kalau nggak, transaksi lama yang di-generate telat bisa salah hitung karena ikut kehitung barang masuk/koreksi yang sebenarnya baru tercatat belakangan
- Tie-breaker buat data di tanggal yang sama (termasuk lintas tabel, misal BPU vs Transaksi) pakai `created_at` (kapan data itu benar-benar dientry ke sistem), bukan `id` — soalnya urutan `id` antar tabel yang beda nggak mencerminkan urutan waktu asli
- Dihitung **per kode barang**, sepanjang riwayat (tidak di-scope per tahun anggaran)
- **Tidak ada** input "saldo awal" manual — total berasal murni dari akumulasi BPU
- Koreksi stok = entri ledger terpisah (bukan overwrite angka), wajib ada alasan → audit trail terjaga. **Scope dipersempit** ke kasus fisik nyata (rusak/hilang/opname) — bukan lagi buat menambal salah input, karena sekarang BPU & Transaksi Keluar punya Edit langsung
- Barang tidak akan bisa diminta (muncul di NPB) kalau belum pernah ada BPU untuk kode itu — divalidasi saat create/edit/import, dengan warning + link cepat ke halaman edit BPU terkait

### 8.3 Matching Kode Barang
- Import BPU & Transaksi Keluar pakai **Nama Barang** (bukan kode) sebagai kunci pencarian ke Master Barang
- Kalau tidak ketemu exact match → sekolah mapping manual sekali → tersimpan sebagai alias (tabel `barang_alias`) supaya upload berikutnya auto-match

### 8.4 Matching Pihak Peminta (Transaksi Keluar)
- Kunci matching: kombinasi **Nama + Jabatan** (persis, bukan sebagian) ke data Pegawai milik sekolah itu — kombinasi dipakai (bukan nama doang) buat menghindari salah orang kalau ada nama pegawai yang kebetulan sama
- Cocok → `pihak_peminta_id` otomatis ke-assign, tetap bisa diubah manual
- Tidak cocok → dikosongkan, sistem tampilkan notifikasi transaksi mana aja yang gagal auto-mapping, dilengkapi manual (combobox search di halaman draft)
- Validasi tambahan: kalau dalam 1 Nomor Referensi, Nama Peminta beda-beda antar baris → warning (indikasi salah ketik di excel)

---

## 9. Non-Functional Requirements

- **Keamanan**: field sensitif (role, status subscription) TIDAK masuk `$fillable` — assignment manual di controller/action. NIP, TTD upload divalidasi tipe file & ukuran.
- **Multi-tenancy**: 1 akun sekolah = 1 tenant, semua query di-scope by `sekolah_id` (pakai Laravel global scope atau middleware).
- **Performa import**: Excel besar (>500 baris) diproses via Laravel Queue (job async), bukan blocking request, kasih progress bar/notifikasi selesai.
- **File storage**: logo, TTD, hasil PDF/Word disimpan di `storage/app/public`, symlink ke `public/storage`.

---

## 10. Out of Scope (v1)

- Multi-user per sekolah (role internal sekolah selain 1 akun utama)
- Notifikasi WA otomatis
- Approval workflow berjenjang (misal butuh approve dulu sebelum generate)
- Laporan/rekap keuangan BKU penuh (fokus v1 cuma generate surat + ledger stok sederhana)

---

## 11. Progress Tracking

Lihat `TASKS.md` untuk checklist detail per fase development.
Kalau sesi chat terputus, gunakan `CONTINUATION_PROMPT.md`.
