<?php

namespace App\Services\ExportManager\Formatters;

use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class EventCategoryExportableDataFormatter implements ExportableDataTemplateInterface
{
    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $category) {
            $temp['name'] = $category->name;
            $temp['slug'] = $category->slug;
            $temp['color'] = $category->color;
            $temp['visibility'] = $category->visibility?->name;
            $temp['distance_in_km'] = $category->distance_in_km;

            $data[] = $temp;
        }

        return $data;
    }
}
