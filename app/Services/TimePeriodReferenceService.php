<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class TimePeriodReferenceService
{
    /**
     * @var string
     */
    private string $timeReference;
    /**
     * @var string
     */
    private string $previousTimeReference;

    public function __construct(string $timeReference)
    {
        $this->timeReference = $timeReference;

        if (Str::endsWith($timeReference, $end = 'h')) {
            $this->previousTimeReference = (((int)rtrim($timeReference, $end)) * 2) . $end;
        } elseif(Str::endsWith($timeReference, $end = 'd')) {
            $this->previousTimeReference = (((int)rtrim($timeReference, $end)) * 2) . $end;
        } elseif(Str::endsWith($timeReference, $end = 'y')) {
            $this->previousTimeReference = (((int)rtrim($timeReference, $end)) * 2) . $end;
        } else $this->previousTimeReference = $timeReference;
    }

    /**
     * @param bool $forPrevious
     * @return Carbon|null
     */
    public function toCarbonInstance(bool $forPrevious = false): ?Carbon
    {
        $timeReference = $forPrevious ? $this->previousTimeReference : $this->timeReference;

        if (Str::endsWith($timeReference, $end = 'h')) {
            return now()->subHours($this->getPeriodNumericalValue($timeReference, $end));
        } elseif(Str::endsWith($timeReference, $end = 'd')) {
            return now()->subDays($this->getPeriodNumericalValue($timeReference, $end));
        } elseif(Str::endsWith($timeReference, $end = 'y')) {
            return now()->subYears($this->getPeriodNumericalValue($timeReference, $end));
        } else return null;
    }

    /**
     * @param string $timeReference
     * @param string $endsWith
     * @return int
     */
    private function getPeriodNumericalValue(string $timeReference, string $endsWith): int
    {
        return (int)rtrim($timeReference, $endsWith);
    }
}
