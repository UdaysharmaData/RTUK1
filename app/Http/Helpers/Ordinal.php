<?php

namespace App\Http\Helpers;

class Ordinal {
	public static function express($number) {
		if(!in_array(($number % 100), array(11,12,13))) {
            switch ($number % 10) { // Handle 1st, 2nd, 3rd
                case 1:  return $number.'st';
                case 2:  return $number.'nd';
                case 3:  return $number.'rd';
            }
        }

        return $number.'th';
	}
}