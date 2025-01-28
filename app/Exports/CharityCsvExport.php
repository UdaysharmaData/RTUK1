<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class CharityCsvExport implements FromArray, WithHeadings
{
    protected $charities;

    public function __construct(array $charities)
    {
        $this->charities = $charities;
    }

    /**
     * Set the headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->charities[0]);
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->charities;
    }
}
