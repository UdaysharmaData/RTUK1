<?php

namespace App\Services\Reporting\Traits;

trait StructureChartDataTrait
{
    /**
     * @param array $data
     * @return array
     */
    protected static function stackedColumnChart(array $data): array
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

    /**
     * @param array $data
     * @return array
     */
    protected static function stackedAreaChart(array $data): array
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
