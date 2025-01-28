<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class RegionCsvExport implements FromArray, WithHeadings
{
    protected $regions;

    public function __construct(array $regions)
    {
        $this->regions = $regions;
    }

    /**
     * Set the headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->regions[0]);
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->regions;
    }
}
