<?php

namespace App\Http\Helpers;

class Years {
	public static function generate($firstYear = null, $lastYear = null) {
        if(!$firstYear) {
        	$firstYear = strtotime(date('Y').' -5 year');
            $firstYear = date('Y', $firstYear);
        }

        if(!$lastYear) {
            $lastYear = strtotime(date('Y').' +5 year');
            $lastYear = date('Y', $lastYear);
        }

        $years = [];
        for($i=$firstYear; $i<=$lastYear; $i++) {
          array_push($years, ['label' => $i, 'value' => $i]);
        }

		return $years;
	}
}
