<?php

namespace App\Contracts;

interface AudienceSourceInterface
{
    /**
     * @return array
     */
    public function data(): array;
}
