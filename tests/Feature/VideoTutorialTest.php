<?php

namespace Tests\Feature;

use App\Models\VideoTutorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoTutorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_ekstrak_id_dari_link_watch(): void
    {
        $video = new VideoTutorial(['url_youtube' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $this->assertSame('dQw4w9WgXcQ', $video->youtube_id);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $video->embed_url);
        $this->assertSame('https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $video->thumbnail_url);
    }

    public function test_ekstrak_id_dari_link_share_youtu_be(): void
    {
        $video = new VideoTutorial(['url_youtube' => 'https://youtu.be/dQw4w9WgXcQ']);

        $this->assertSame('dQw4w9WgXcQ', $video->youtube_id);
    }

    public function test_ekstrak_id_dari_link_youtube_dengan_parameter_tambahan(): void
    {
        $video = new VideoTutorial(['url_youtube' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLxyz&index=2']);

        $this->assertSame('dQw4w9WgXcQ', $video->youtube_id);
    }

    public function test_ekstrak_id_dari_link_embed(): void
    {
        $video = new VideoTutorial(['url_youtube' => 'https://www.youtube.com/embed/dQw4w9WgXcQ']);

        $this->assertSame('dQw4w9WgXcQ', $video->youtube_id);
    }

    public function test_link_yang_tidak_valid_balikin_null(): void
    {
        $video = new VideoTutorial(['url_youtube' => 'https://example.com/bukan-video']);

        $this->assertNull($video->youtube_id);
        $this->assertNull($video->embed_url);
        $this->assertNull($video->thumbnail_url);
    }

    public function test_landing_page_menampilkan_video_tutorial_yang_sudah_diinput_admin(): void
    {
        VideoTutorial::create([
            'judul' => 'Cara Upload Barang Masuk',
            'deskripsi' => 'Panduan lengkap upload BPU dari Excel.',
            'url_youtube' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'urutan' => 1,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Cara Upload Barang Masuk')
            ->assertSee('Panduan lengkap upload BPU dari Excel.')
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_landing_page_tidak_error_kalau_belum_ada_video(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertDontSee('Video Tutorial');
    }

    public function test_video_tampil_sesuai_urutan(): void
    {
        VideoTutorial::create(['judul' => 'Video Kedua', 'url_youtube' => 'https://youtu.be/dQw4w9WgXcQ', 'urutan' => 2]);
        VideoTutorial::create(['judul' => 'Video Pertama', 'url_youtube' => 'https://youtu.be/dQw4w9WgXcQ', 'urutan' => 1]);

        $response = $this->get('/');

        $content = $response->getContent();
        $posisiPertama = strpos($content, 'Video Pertama');
        $posisiKedua = strpos($content, 'Video Kedua');

        $this->assertLessThan($posisiKedua, $posisiPertama);
    }
}
