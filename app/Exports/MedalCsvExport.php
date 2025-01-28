<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;

class MedalCsvExport implements FromArray, WithHeadings
{
    protected $medals;

    public function __construct(Collection $medals)
    {
        $this->medals = $medals;
    }

    /**
     * Set the headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->_getMedals()[0]);
    }

    /**
     * @return array
     */
    public function array(): array
    {
        return $this->_getMedals();
    }


    private function _getMedals()
    {
        return $this->medals->map(function ($medal) {
            return [
                'name' => $medal->name,
                'slug' => $medal->slug,
                'type' => $medal->type->value,
                'event' => $medal->medalable->medalable_type == Event::class ? $medal->medalable->name : null,
                'category' => $medal->medalable->medalable_type == EventCategory::class ? $medal->medalable->name : null,
                'description' => $medal->description,
            ];
        })->toArray();
    }
}
