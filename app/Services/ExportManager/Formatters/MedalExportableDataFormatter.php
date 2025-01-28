<?php

namespace App\Services\ExportManager\Formatters;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class MedalExportableDataFormatter implements ExportableDataTemplateInterface
{
    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $medal) {
            $temp['name'] = $medal->name;
            $temp['slug'] = $medal->slug;
            $temp['type'] = $medal->type->value;
            $temp['event'] = $medal->medalable_type == Event::class ? $medal->medalable->name : null;
            $temp['category'] = $medal->medalable_type == EventCategory::class ? $medal->medalable->name : null;
            $temp['description'] = $medal->description;

            $data[] = $temp;
        }

        return $data;
    }
}
