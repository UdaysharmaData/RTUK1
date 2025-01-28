<?php

namespace App\Services\Reporting\Traits;

trait PercentageChangeTrait
{
    /**
     * @param int $currentValue
     * @param int $previousValue
     * @return float
     */
    protected static function percentageChange(int $currentValue, int $previousValue): float
    {
        if ($previousValue === 0) {
            if($currentValue > 0) {
                $percentageChange = 100;
            } elseif($currentValue < 0) {
                $percentageChange = -100;
            } else {
                $percentageChange = 0;
            }
        } else {
            $percentageChange = (($currentValue - $previousValue) / $previousValue) * 100;
        }

        return round($percentageChange, 1);
    }
}