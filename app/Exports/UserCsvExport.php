<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class UserCsvExport implements FromArray, WithHeadings
{
    protected $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * Set the headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->users[0]);
    }

    /**
    * @return array
    */
    public function array(): array
    {
        return $this->users;
    }
}
