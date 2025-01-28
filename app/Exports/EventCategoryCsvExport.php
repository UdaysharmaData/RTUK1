<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class EventCategoryCsvExport implements FromArray, WithHeadings
{
    protected $eventCategories;

    public function __construct(array $eventCategories)
    {
        $this->eventCategories = $eventCategories;
    }

    /**
     * Set the headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->eventCategories[0]);
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->eventCategories;
    }
}
