<?php

namespace App\Http\Helpers;

class TextHelper {

    /**
     * @param  string|null  $text
     * @return string
     */
	public static function purify(?string $text): string
    {
        // Strip HTML Tags
        $text = strip_tags($text);

        // Clean up things like &amp;
        $text = html_entity_decode($text);

        // Strip out any url-encoded stuff
        $text = urldecode($text);

        // Replace non-AlNum characters with space
        $text = preg_replace('/[^A-Za-z0-9]/', ' ', $text);

        // Replace Multiple spaces with single space
        $text = preg_replace('/ +/', ' ', $text);

        // Trim the string of leading/trailing space
        $text = trim($text);

        return $text;
    }
}
