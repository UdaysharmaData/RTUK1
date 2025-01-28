<?php

namespace Database\Traits;

use Carbon\Carbon;

trait FormatDate
{
    /**
     * Do some checks and return the date passed to the function or the current date.
     * @param string|null $date
     * @return object
     */
    protected function dateOrNow(?string $date): object
    {
        if ($date == '0000-00-00 00:00:00' || $date == '0000-00-00' || $date == '' || $date == ' ' || !$date) {
            return Carbon::now();
        }

        return Carbon::parse($date);
    }

    /**
     * Do some checks and return the date passed to the function or null.
     * @param string|null $date
     * @return object|null
     */
    protected function dateOrNull(?string $date): object|null
    {
        if ($date == '0000-00-00 00:00:00' || $date == '0000-00-00' || $date == '' || $date == ' ' || !$date) {
            return null;
        }

        return Carbon::parse($date);
    }
}