<?php

namespace App\Services\ExportManager\Interfaces;

interface ExportableDataTemplateInterface
{
    public function format(mixed $list): array;
}
