<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class InvoiceCsvExport implements FromArray, WithHeadings
{
    protected $invoices;

    public function __construct(array $invoices)
    {
        $this->invoices = $invoices;
    }

    /**
     * Set the headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->invoices[0]);
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->invoices;
    }
}
