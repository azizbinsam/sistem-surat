<?php

namespace App\View\Components;

use App\Models\Banner;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-banner-slider lokasi="dashboard" /> atau lokasi="landing".
 * Tidak merender apa pun kalau tidak ada banner yang sedang tayang.
 */
class BannerSlider extends Component
{
    public Collection $banners;

    public function __construct(public string $lokasi)
    {
        $this->banners = Banner::tayang($lokasi)->get();
    }

    public function shouldRender(): bool
    {
        return $this->banners->isNotEmpty();
    }

    public function render(): View
    {
        return view('components.banner-slider');
    }
}
