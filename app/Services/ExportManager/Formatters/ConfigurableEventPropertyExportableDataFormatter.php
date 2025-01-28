<?php

namespace App\Services\ExportManager\Formatters;

use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class ConfigurableEventPropertyExportableDataFormatter implements ExportableDataTemplateInterface
{
    /**
     * @var array
     */
    private array $relations;

    /**
     * @param mixed $list
     * @return array
     */
    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $eventProperty) {
            $temp['name'] = $eventProperty->name;
            $temp['slug'] = $eventProperty->slug;
            $temp['description'] = $eventProperty->description;

            if ($eventProperty->meta) {
                $temp['meta_title'] = $eventProperty->meta->title;
                $temp['meta_description'] = $eventProperty->meta->description;
                $temp['meta_keywords'] = $eventProperty->meta->keywords;
            }

            $data[] = $temp;
        }

        return $data;
    }
}
