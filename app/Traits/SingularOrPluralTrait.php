<?php

namespace App\Traits;

trait SingularOrPluralTrait
{
    /**
     * Get the word to use based on the size of the data
     *
     * @param  array   $text should be an array of size 2. The first index should contain the singular form of the value and the second index the plural form.
     * @param  mixed   $data
     * @return ?string
     */
    protected static function singularOrPlural(array $text, $data): ?string
    {
        $data = is_array($data) // Cast string to array if it's not an array
            ? $data
            : collect($data)->toArray();
        
        return count($data) > 1 ? $text[1] : $text[0];
    }
}