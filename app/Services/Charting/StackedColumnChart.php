<?php

namespace App\Services\Charting;

class StackedColumnChart implements ChartDataInterface
{

    /**
     * @inheritDoc
     */
    public function format(array $data): array
    {
        $updated = [];

        for ($x = 0; $x < count($data); $x++) {
            $category = $data[$x]['categories'];
            $count = count($category);

            if ($count > 0) {
                for ($y = 0; $y < $count; $y++) {
                    $updated['categories'][$y]['name'] = $category[$y]['name'];
                    $updated['categories'][$y]['data'][$x] = $category[$y]['total'];
                }
            } else $updated['categories'] = [];

            $updated['months'][$x] = $data[$x]['month'];
        }

        return $updated;
    }
}
