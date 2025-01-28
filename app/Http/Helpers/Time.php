<?php

namespace App\Http\Helpers;

Class Time {
	public static function difference($time1, $time2, $format=null) {
		$d = null;

		switch ($format) {
			case 'seconds':
				$d = Time::differenceInSeconds($time1, $time2);
				break;
			
			default:
				$d = Time::format(Time::differenceInSeconds($time1, $time2));
				break;
		}
		return $d;
	}

	public static function differenceInSeconds($time1, $time2) {
		$time1 = Time::timeInSeconds($time1);
		$time2 = Time::timeInSeconds($time2);

		return abs($time1 - $time2);
	}

	public static function timeInSeconds($time) {
		$time = explode(':', $time);

		return count($time) == 3 ? ((((int) $time[0] * 60) + (int) $time[1]) * 60) + (int) $time[2] : null;
	}

	public static function timeAsArray($time) {
		return explode(':', $time);
	}

	public static function format($timeInSeconds) {
		$hours = (int) ($timeInSeconds / 3600);
        $offHours = $timeInSeconds % 3600;

        $minutes = (int) ($offHours / 60);
        $offMinutes = $offHours % 60;

        $seconds = $offMinutes;

        return sprintf('%02d', $hours).':'.sprintf('%02d', $minutes).':'.sprintf('%02d', $seconds);
	}
}