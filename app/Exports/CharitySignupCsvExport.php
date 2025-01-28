<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class CharitySignupCsvExport implements FromCollection, WithHeadings
{
    protected $enquiries;

    public function __construct(Collection $enquiries)
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
        return array_keys($this->enquiries->first()->toArray());
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->enquiries;
    }
}
