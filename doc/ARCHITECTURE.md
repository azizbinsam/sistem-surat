# ARCHITECTURE.md — Skema Database & Struktur Sistem

Pendamping `PRD.md`. Baca ini bareng-bareng sebelum mulai bikin migration.

---

## 1. Prinsip Desain

- **Normalized**, bukan wide-column. Setiap transaksi punya banyak item → relasi hasMany, bukan kolom `barang_1, barang_2, ... barang_7`.
- **Multi-tenant**: hampir semua tabel punya `sekolah_id` (kecuali tabel global seperti `users`, `paket_subscription`).
- **Soft delete** dipakai di tabel master (barang, pegawai) supaya data historis surat lama tidak rusak kalau ada yang dihapus.

---

## 2. Daftar Tabel

### `users`
Akun login (admin & sekolah pakai tabel yang sama, dibedakan kolom `role`).
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| google_id | string nullable | dari OAuth |
| role | enum(admin, sekolah) | |
| sekolah_id | FK → sekolah, nullable | null kalau role=admin |
| password | string nullable | wajib diisi kalau daftar via email, null kalau daftar via Google saja |
| email_verified_at | timestamp nullable | untuk verifikasi email (khusus daftar via email+password) |

### `sekolah`
Data profil sekolah (1 row = 1 tenant).
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| nama_sekolah | string | |
| nama_pemerintah | string | |
| alamat | text | |
| tempat | string | dipakai di surat |
| kontak_wa | string | |
| email | string | |
| logo_sekolah | string nullable | path storage |
| logo_kabupaten | string nullable | path storage |
| kode_sekolah | string | dipakai di format nomor surat, misal `SDN3RKST` |
| nama_dinas | string | untuk kop surat, misal "DINAS PENDIDIKAN" |
| nama_korwil | string nullable | untuk kop surat, misal "KORWIL SATUAN PENDIDIKAN" — nullable karena tidak semua daerah pakai |
| jabatan_resmi_sppb | string | default "Kuasa Pengguna Barang" — label jabatan yang dicetak di TTD SPPB, terpisah dari `pegawai.jabatan` milik Kepala Sekolah |
| kode_klasifikasi_surat | string | contoh: "000.2.3.1" — prefix konstan di semua nomor surat, kemungkinan kode klasifikasi anggaran, beda per sekolah/daerah |
| format_kode_npb | string | label pojok kanan atas surat NPB, misal "FORMAT II.I.6" |
| format_kode_spb | string | label pojok kanan atas surat SPB, misal "FORMAT II.I.7" |
| format_kode_sppb | string | label pojok kanan atas surat SPPB, misal "FORMAT II.I.8" |

### `paket_subscription`
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| nama_paket | string | misal "Bulanan", "Tahunan" |
| harga | decimal | |
| durasi_hari | int | |

### `subscriptions`
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| sekolah_id | FK | |
| paket_id | FK | |
| status | enum(aktif, hold, expired) | |
| tanggal_mulai | date | |
| tanggal_berakhir | date | |
| fersaku_payment_id | string nullable | reference/ID transaksi dari Fersaku (dulu direncanakan `midtrans_order_id`, berubah karena ganti payment gateway) |
| dibuat_manual_oleh | FK users, nullable | kalau admin extend manual |

### `master_barang`
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| sekolah_id | FK | |
| kode_barang | string | |
| nama_barang | string | |
| kategori | string nullable | |
| satuan_default | string | |
| keperluan_default | text nullable | auto-suggest saat isi excel/form transaksi keluar |
| spesifikasi_default | string nullable | auto-suggest/fallback kalau spesifikasi di transaksi keluar dikosongkan |

### `barang_alias`
Mapping nama-di-excel → master_barang, biar auto-match makin akurat tiap upload.
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| sekolah_id | FK | |
| master_barang_id | FK | |
| nama_alias | string | teks persis dari excel |
| spesifikasi_alias | string nullable | |

### `barang_masuk` (header BPU)
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| sekolah_id | FK | |
| nomor_bpu | string | |
| tanggal | date | |

### `barang_masuk_item`
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| barang_masuk_id | FK | |
| master_barang_id | FK | |
| spesifikasi | string | |
| satuan | string | |
| jumlah | int | |

### `koreksi_stok`
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| sekolah_id | FK | |
| master_barang_id | FK | |
| tanggal | date | |
| jumlah | int | boleh negatif |
| alasan | text | wajib |
| user_id | FK users | siapa yang input |

### `pegawai`
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| sekolah_id | FK | |
| nama | string | |
| nip | string | |
| jabatan | string | |
| kategori | enum(kepala_sekolah, pengurus_barang_pembantu, guru, tendik) | |
| ttd_path | string nullable | |

### `transaksi` (header, = 1 bundel NPB+SPB+SPPB)
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| sekolah_id | FK | |
| nomor_referensi_asal | string | dari kolom excel, buat traceability |
| nomor_npb | string nullable | terisi saat digenerate |
| nomor_spb | string nullable | |
| nomor_sppb | string nullable | |
| tanggal_npb | date | |
| tanggal_spb | date nullable | |
| tanggal_sppb | date nullable | |
| pihak_peminta_id | FK pegawai, nullable | null = belum di-mapping |
| status | enum(draft, siap_generate, selesai) | |

### `transaksi_item`
| Kolom | Tipe | Ket |
|---|---|---|
| id | bigint PK | |
| transaksi_id | FK | |
| master_barang_id | FK | |
| spesifikasi | string **nullable** | fallback ke `master_barang.nama_barang` (atau `spesifikasi_default`) kalau kosong saat generate surat |
| jumlah | int | |
| satuan | string | |
| keperluan | string | |

---

## 3. Relasi Ringkas

```
sekolah 1---N users
sekolah 1---N subscriptions
sekolah 1---N master_barang
sekolah 1---N pegawai
sekolah 1---N barang_masuk
sekolah 1---N transaksi

master_barang 1---N barang_alias
master_barang 1---N barang_masuk_item
master_barang 1---N koreksi_stok
master_barang 1---N transaksi_item

barang_masuk 1---N barang_masuk_item

transaksi 1---N transaksi_item
transaksi N---1 pegawai (pihak_peminta)
```

## 4. Query Kunci — Hitung Sisa Persediaan

**Bug ditemukan pas testing** (Fase 14): versi awal cuma nge-filter tanggal di `totalKeluar`, sedangkan `totalMasuk` dan `totalKoreksi` dihitung tanpa batas tanggal sama sekali — bikin salah hitung kalau ada transaksi lama yang di-generate telat, sementara udah ada BPU/koreksi baru yang tercatat belakangan. Versi yang benar, ketiganya pakai cutoff tanggal yang sama:

```php
// Pseudocode, dijalankan per master_barang_id, dengan cutoff ke transaksi tertentu
$cutoffTanggal = $transaksi->tanggal_npb;
$cutoffCreatedAt = $transaksi->created_at; // tie-breaker lintas tabel buat tanggal yang sama

$totalMasuk = BarangMasukItem::where('master_barang_id', $id)
    ->whereHas('barangMasuk', fn ($q) => $q->beforeCutoff('tanggal', $cutoffTanggal, $cutoffCreatedAt))
    ->sum('jumlah');

$totalKoreksi = KoreksiStok::where('master_barang_id', $id)
    ->beforeCutoff('tanggal', $cutoffTanggal, $cutoffCreatedAt)
    ->sum('jumlah');

$totalKeluarSebelumIni = TransaksiItem::where('master_barang_id', $id)
    ->whereHas('transaksi', fn ($q) => $q->beforeCutoff('tanggal_npb', $cutoffTanggal, $cutoffCreatedAt)
        ->where('id', '!=', $transaksi->id))
    ->sum('jumlah');

$sisa = $totalMasuk + $totalKoreksi - $totalKeluarSebelumIni;
```
`beforeCutoff` = scope/helper: `tanggal < cutoffTanggal OR (tanggal = cutoffTanggal AND created_at < cutoffCreatedAt)`. Dipakai konsisten di 3 tempat itu, bukan cuma di query keluar.

Untuk "sisa stok saat ini" (halaman ringkasan, bukan konteks 1 transaksi spesifik), cutoff-nya `now()` — nggak exclude apapun.

Detail implementasi (index database, caching kalau perlu) dibahas pas masuk fase coding.

---

## 5. Catatan untuk Migration

Urutan bikin migration (biar foreign key nggak error):
1. `sekolah`
2. `users` (butuh sekolah_id)
3. `paket_subscription`
4. `subscriptions`
5. `master_barang`
6. `barang_alias`
7. `barang_masuk`
8. `barang_masuk_item`
9. `koreksi_stok`
10. `pegawai`
11. `transaksi`
12. `transaksi_item`

Detail command & kode migration akan digenerate step-by-step pas eksekusi Fase 2 di `TASKS.md`.
