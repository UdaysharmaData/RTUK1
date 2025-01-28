<?php

namespace App\Services\Charting;

interface ChartDataInterface
{
    /**
     * @param array $data
     * @return array
     */
    public function format(array $data): array;
}
