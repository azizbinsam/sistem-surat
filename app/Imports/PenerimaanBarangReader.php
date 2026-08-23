<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PenerimaanBarangReader implements ToCollection, WithHeadingRow
{
    public \Illuminate\Support\Collection $rows;

    public function collection(\Illuminate\Support\Collection $rows): void
    {
        $this->rows = $rows;
    }
}
