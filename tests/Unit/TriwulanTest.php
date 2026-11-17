<?php

namespace Tests\Unit;

use App\Support\Triwulan;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TriwulanTest extends TestCase
{
    public function test_dari_tanggal_memetakan_bulan_ke_triwulan(): void
    {
        $this->assertSame(1, Triwulan::dariTanggal('2027-01-01'));
        $this->assertSame(1, Triwulan::dariTanggal('2027-03-31'));
        $this->assertSame(2, Triwulan::dariTanggal('2027-04-01'));
        $this->assertSame(3, Triwulan::dariTanggal('2027-09-30'));
        $this->assertSame(4, Triwulan::dariTanggal('2027-12-31'));
    }

    public function test_rentang_tiap_triwulan(): void
    {
        [$awal, $akhir] = Triwulan::rentang(2027, 1);
        $this->assertSame('2027-01-01', $awal->toDateString());
        $this->assertSame('2027-03-31', $akhir->toDateString());

        [$awal, $akhir] = Triwulan::rentang(2027, 2);
        $this->assertSame('2027-04-01', $awal->toDateString());
        $this->assertSame('2027-06-30', $akhir->toDateString());

        [$awal, $akhir] = Triwulan::rentang(2027, 3);
        $this->assertSame('2027-07-01', $awal->toDateString());
        $this->assertSame('2027-09-30', $akhir->toDateString());

        [$awal, $akhir] = Triwulan::rentang(2027, 4);
        $this->assertSame('2027-10-01', $awal->toDateString());
        $this->assertSame('2027-12-31', $akhir->toDateString());
    }

    public function test_triwulan_di_luar_1_sampai_4_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Triwulan::rentang(2027, 5);
    }
}
