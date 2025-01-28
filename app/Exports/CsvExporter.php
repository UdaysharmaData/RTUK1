<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CsvExporter implements FromArray, WithHeadings
{
    use Exportable;

    public function __construct(protected array $data)
    {

    }

    /**
     * Set the headings
     *
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->data[0]);
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->data;
    }
}
