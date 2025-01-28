<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class PartnerEventCsvExport implements FromArray, WithHeadings
{
    protected $participants;

    public function __construct(array $participants)
    {
        $this->participants = $participants;
    }

    /**
     * Set the headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->participants[0]);
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->participants;
    }
}
