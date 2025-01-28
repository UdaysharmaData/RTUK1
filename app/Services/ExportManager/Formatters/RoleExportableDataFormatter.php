<?php

namespace App\Services\ExportManager\Formatters;

use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class RoleExportableDataFormatter implements ExportableDataTemplateInterface
{
    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $role) {
            $temp['name'] = $role->name;
            $temp['description'] = $role->description;
            $temp['created_at'] = $role->created_at;

            $data[] = $temp;
        }

        return $data;
    }
}
