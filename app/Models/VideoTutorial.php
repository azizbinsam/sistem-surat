<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoTutorial extends Model
{
    protected $table = 'video_tutorial';

    protected $fillable = [
        'judul',
        'deskripsi',
        'url_youtube',
        'urutan',
    ];

    /**
     * Ekstrak ID video dari berbagai bentuk link YouTube yang mungkin admin paste:
     * - https://www.youtube.com/watch?v=XXXXXXXXXXX
     * - https://youtu.be/XXXXXXXXXXX
     * - https://www.youtube.com/embed/XXXXXXXXXXX
     * - https://www.youtube.com/shorts/XXXXXXXXXXX
     * Balikin null kalau formatnya nggak dikenali, biar view bisa fallback dengan aman.
     */
    public function getYoutubeIdAttribute(): ?string
    {
        $pola = '/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/';

        if (preg_match($pola, $this->url_youtube, $cocok)) {
            return $cocok[1];
        }

        return null;
    }

    public function getEmbedUrlAttribute(): ?string
    {
        $id = $this->youtube_id;

        return $id ? "https://www.youtube.com/embed/{$id}" : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        $id = $this->youtube_id;

        return $id ? "https://img.youtube.com/vi/{$id}/hqdefault.jpg" : null;
    }
}
