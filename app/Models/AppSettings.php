<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton — cuma ada 1 baris di tabel ini (diisi lewat migration).
 * Dikelola dari Panel Admin (Fase 20), dibaca oleh landing page & dashboard.
 */
class AppSettings extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'nama_aplikasi',
        'logo_aplikasi',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['nama_aplikasi' => 'Sistem Surat']);
    }
}
