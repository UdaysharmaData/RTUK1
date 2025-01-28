<?php

namespace App\Services\ExportManager\Formatters;

use App\Services\ExportManager\Interfaces\ExportableDataTemplateInterface;

class PartnerExportableDataFormatter implements ExportableDataTemplateInterface
{
    public function format(mixed $list): array
    {
        $data = [];

        foreach ($list as $partner) {
            $temp['name'] = $partner->name;
            $temp['slug'] = $partner->slug;
            $temp['description'] = $partner->description;
            $temp['offer'] = $partner->offer;
            $temp['website'] = $partner->website;
            $temp['code'] = $partner->code;
            $temp['expiry'] = $partner->expiry;

            $data[] = $temp;
        }

        return $data;
    }
}
