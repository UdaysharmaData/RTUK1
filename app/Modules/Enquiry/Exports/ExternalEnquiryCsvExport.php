<?php

namespace App\Modules\Enquiry\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class ExternalEnquiryCsvExport implements FromArray, WithHeadings
{
    protected $enquiries;

    public function __construct(array $enquiries)
    {
        $this->enquiries = $enquiries;
    }

    /**
     * Set the headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->enquiries[0]);
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->enquiries;
    }
}
