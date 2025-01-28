<?php

namespace Tests\Helpers;

class General
{
    /**
     * @return mixed
     */
    public static function randomizedEnquiryType(): mixed
    {
        $options = self::validCategories();
        $index = array_rand($options);
        return $options[$index];
    }

    /**
     * @return array
     */
    private static function validCategories(): array
    {
        return array_map(
            fn ($category) => array_keys($category)[0],
            config('apiclient.enquiries')[clientName()] ?? []
        );
    }
}
