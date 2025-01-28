<?php

namespace App\Services\Charting;

class StackedAreaChart implements ChartDataInterface
{

    /**
     * @inheritDoc
     */
    public function format(array $data): array
    {
        $updated = [];

        for ($x = 0; $x < count($data); $x++) {
            for ($y = 0; $y < count($category = $data[$x]['categories']); $y++) {
                $updated['categories'][$y]['name'] = $category[$y]['name'];
                $updated['categories'][$y]['data'][$x]['x'] = $data[$x]['month'];
                $updated['categories'][$y]['data'][$x]['y'] = (float) $category[$y]['total'];
            }
        }

        return $updated;
    }
}
