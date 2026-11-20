<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Banner gambar untuk dashboard sekolah & landing page (doc/LANGGANAN.md §11).
 * Setiap banner punya DUA gambar: desktop (1600×400) dan ponsel (800×600) supaya tampil pas di semua layar.
 */
class Banner extends Model
{
    protected $table = 'banner';

    // Ukuran gambar yang diwajibkan (lebar, tinggi) — satu sumber untuk validasi & teks bantuan di panel admin.
    public const DESKTOP = [1600, 400];
    public const MOBILE = [800, 600];

    public const LOKASI = [
        'dashboard' => 'Dashboard sekolah',
        'landing' => 'Landing page',
        'keduanya' => 'Dashboard & landing page',
    ];

    protected $fillable = [
        'judul', 'gambar_desktop', 'gambar_mobile', 'teks_alt', 'link_url',
        'lokasi', 'urutan', 'aktif', 'tayang_mulai', 'tayang_selesai',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'urutan' => 'integer',
        'tayang_mulai' => 'datetime',
        'tayang_selesai' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Gambar lama dihapus dari disk saat diganti, dan semua gambarnya dihapus saat banner dihapus,
        // supaya storage tidak menumpuk file yatim.
        static::updating(function (self $banner) {
            foreach (['gambar_desktop', 'gambar_mobile'] as $kolom) {
                $lama = $banner->getOriginal($kolom);
                if ($banner->isDirty($kolom) && $lama) {
                    Storage::disk('public')->delete($lama);
                }
            }
        });

        static::deleting(function (self $banner) {
            Storage::disk('public')->delete(array_filter([$banner->gambar_desktop, $banner->gambar_mobile]));
        });
    }

    /**
     * Banner yang sedang tayang untuk sebuah lokasi, berurutan:
     * aktif, lokasinya cocok (atau "keduanya"), dan di dalam jadwal tayang (kalau jadwal diisi).
     */
    public function scopeTayang(Builder $query, string $lokasi): Builder
    {
        return $query
            ->where('aktif', true)
            ->whereIn('lokasi', [$lokasi, 'keduanya'])
            ->where(fn($q) => $q->whereNull('tayang_mulai')->orWhere('tayang_mulai', '<=', now()))
            ->where(fn($q) => $q->whereNull('tayang_selesai')->orWhere('tayang_selesai', '>=', now()))
            ->orderBy('urutan')
            ->orderBy('id');
    }

    public function urlDesktop(): string
    {
        return asset('storage/' . $this->gambar_desktop);
    }

    public function urlMobile(): string
    {
        return asset('storage/' . $this->gambar_mobile);
    }

    /**
     * Tautan yang aman dipasang di href: hanya http(s) atau path relatif satu garis miring ("/daftar").
     * Skema lain (javascript:, data:, //host) ditolak — pertahanan kedua kalau validasi form terlewat.
     */
    public function linkAman(): ?string
    {
        $link = trim((string) $this->link_url);

        return preg_match('~^(https?://|/(?!/))~i', $link) ? $link : null;
    }

    public function statusTayang(): string
    {
        if (!$this->aktif) {
            return 'Nonaktif';
        }
        if ($this->tayang_mulai && $this->tayang_mulai->isFuture()) {
            return 'Terjadwal';
        }
        if ($this->tayang_selesai && $this->tayang_selesai->isPast()) {
            return 'Berakhir';
        }

        return 'Tayang';
    }
}
