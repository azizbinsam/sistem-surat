@php
    $idSlider = 'bnr-' . \Illuminate\Support\Str::random(6);
    $jumlah = $banners->count();
@endphp

{{-- Slider banner. wire:ignore: supaya Livewire tidak merombak DOM slider saat halaman dirender ulang.
     Satu banner = gambar saja. Lebih dari satu = ganti otomatis tiap 5 detik (berhenti saat disorot mouse/fokus
     dan tidak berjalan sama sekali bagi pengguna yang meminta gerakan dikurangi). Tanpa Alpine/library apa pun,
     jadi jalan di landing page (tanpa Livewire) maupun dashboard. --}}
<div id="{{ $idSlider }}" data-banner-slider data-interval="5000" wire:ignore role="region" aria-roledescription="carousel"
    aria-label="Banner informasi"
    class="relative w-full overflow-hidden rounded-xl bg-zinc-100 aspect-[4/3] md:aspect-[4/1]">
    @foreach ($banners as $i => $banner)
        @php $link = $banner->linkAman(); @endphp
        <div data-slide
            class="absolute inset-0 transition-opacity duration-500 {{ $i === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}"
            @if ($i > 0) aria-hidden="true" @endif>
            @if ($link)
                <a href="{{ $link }}" class="block w-full h-full"
                    @if (str_starts_with(strtolower($link), 'http')) target="_blank" rel="noopener noreferrer" @endif>
            @endif
            <picture>
                <source media="(min-width: 768px)" srcset="{{ $banner->urlDesktop() }}">
                <img src="{{ $banner->urlMobile() }}" alt="{{ $banner->teks_alt }}" width="800" height="600"
                    class="w-full h-full object-cover" @if ($i > 0) loading="lazy" @endif>
            </picture>
            @if ($link)
                </a>
            @endif
        </div>
    @endforeach

    @if ($jumlah > 1)
        <div class="absolute bottom-2 inset-x-0 flex justify-center gap-2 z-10">
            @foreach ($banners as $i => $banner)
                <button type="button" data-dot="{{ $i }}" aria-label="Banner {{ $i + 1 }}"
                    class="w-2.5 h-2.5 rounded-full shadow ring-1 ring-black/10 {{ $i === 0 ? 'bg-white' : 'bg-white/50' }}"></button>
            @endforeach
        </div>
    @endif
</div>

@if ($jumlah > 1)
    <script>
        (function () {
            // Fungsi didefinisikan sekali; pemanggilan berikutnya (mis. setelah wire:navigate) hanya menjalankan ulang inisialisasi.
            if (window.__bannerSliderInit) { window.__bannerSliderInit(); return; }

            function mulai(root) {
                if (root.dataset.ready) return;
                root.dataset.ready = '1';

                var slides = root.querySelectorAll('[data-slide]');
                var dots = root.querySelectorAll('[data-dot]');
                var ms = parseInt(root.dataset.interval, 10) || 5000;
                var sekarang = 0, timer = null;

                function tampil(i) {
                    sekarang = (i + slides.length) % slides.length;
                    slides.forEach(function (s, n) {
                        var aktif = n === sekarang;
                        s.classList.toggle('opacity-100', aktif);
                        s.classList.toggle('opacity-0', !aktif);
                        s.classList.toggle('pointer-events-none', !aktif);
                        s.setAttribute('aria-hidden', aktif ? 'false' : 'true');
                    });
                    dots.forEach(function (d, n) {
                        d.classList.toggle('bg-white', n === sekarang);
                        d.classList.toggle('bg-white/50', n !== sekarang);
                    });
                }

                function berhenti() { if (timer) { clearInterval(timer); timer = null; } }

                function jalan() {
                    berhenti();
                    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                    timer = setInterval(function () {
                        if (!root.isConnected) { berhenti(); return; } // halaman sudah berganti
                        tampil(sekarang + 1);
                    }, ms);
                }

                dots.forEach(function (d) {
                    d.addEventListener('click', function () { tampil(parseInt(d.dataset.dot, 10)); jalan(); });
                });
                root.addEventListener('mouseenter', berhenti);
                root.addEventListener('mouseleave', jalan);
                root.addEventListener('focusin', berhenti);
                root.addEventListener('focusout', jalan);

                jalan();
            }

            window.__bannerSliderInit = function () {
                document.querySelectorAll('[data-banner-slider]').forEach(mulai);
            };
            window.__bannerSliderInit();
            document.addEventListener('livewire:navigated', window.__bannerSliderInit);
        })();
    </script>
@endif
