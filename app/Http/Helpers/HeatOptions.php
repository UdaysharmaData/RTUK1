<?php

namespace App\Http\Helpers;

class HeatOptions {
	public static function generate($start = null, $end = null) {
		$array = array(
			'08:30 - 09:00',
			'09:00 - 09:30',
			'09:30 - 10:00',
			'10:00 - 10:30',
			'10:30 - 11:00',
			'11:00 - 11:30',
			'11:30 - 12:00',
			'12:00 - 12:30',
			'12:30 - 13:00',
			'13:00 - 13:30',
			'13:30 - 14:00',
			'14:00 - 14:30',
			'14:30 - 15:00',
			'15:00 - 15:30',
			'15:30 - 16:00',
			'16:00 - 16:30',
			'16:30 - 17:00',
			'17:00 - 17:30',
			'17:30 - 18:00',
			'18:00 - 18:30',
			'18:30 - 19:00',
			'19:00 - 19:30',
			'19:30 - 20:00',
			'20:00 - 20:30',
			'20:30 - 21:00'
		);

		if($start) {
			$idx = array_search($start, $array);
			$array = array_slice($array, $idx);
		}

		if($end) {
			$idx = array_search($end, $array);
			array_splice($array, ($idx + 1));
		}

        $times = [];

		foreach($array as $arr) {
          array_push($times, ['label' => $arr, 'value' => $arr]);
        }

		return $times;
	}
}
