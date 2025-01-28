<?php
namespace App\Services\GlobalSearchService\Exceptions;

use Exception;

class InvalidSearchableOptionException extends Exception
{
    public static function notAValidOption(string $option): self
    {
        return new self("Option `{$option}` is not a valid filter.");
    }
}