<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use PHPUnit\Framework\TestCase;

class TerbilangTest extends TestCase
{
    public function test_angka_dasar_dan_belasan(): void
    {
        $this->assertSame('nol rupiah', strtolower(Terbilang::rupiah(0)));
        $this->assertSame('Satu rupiah', Terbilang::rupiah(1));
        $this->assertSame('Sebelas rupiah', Terbilang::rupiah(11));
        $this->assertSame('Tujuh belas rupiah', Terbilang::rupiah(17));
        $this->assertSame('Dua puluh satu rupiah', Terbilang::rupiah(21));
    }

    public function test_seratus_dan_seribu_memakai_se(): void
    {
        $this->assertSame('Seratus rupiah', Terbilang::rupiah(100));
        $this->assertSame('Seratus sebelas rupiah', Terbilang::rupiah(111));
        $this->assertSame('Dua ratus rupiah', Terbilang::rupiah(200));
        $this->assertSame('Seribu rupiah', Terbilang::rupiah(1000));
        $this->assertSame('Seribu lima ratus rupiah', Terbilang::rupiah(1500));
        $this->assertSame('Dua ribu rupiah', Terbilang::rupiah(2000));
    }

    public function test_nominal_invoice_langganan(): void
    {
        $this->assertSame('Tiga puluh sembilan ribu tujuh belas rupiah', Terbilang::rupiah(39017));
        $this->assertSame('Seratus dua puluh sembilan ribu empat ratus lima puluh enam rupiah', Terbilang::rupiah(129456));
        $this->assertSame('Seratus dua puluh sembilan ribu rupiah', Terbilang::rupiah(129000));
    }

    public function test_juta_dan_miliar(): void
    {
        $this->assertSame('Satu juta rupiah', Terbilang::rupiah(1_000_000));
        $this->assertSame('Dua juta lima ratus ribu rupiah', Terbilang::rupiah(2_500_000));
        $this->assertSame('Satu miliar rupiah', Terbilang::rupiah(1_000_000_000));
    }
}
