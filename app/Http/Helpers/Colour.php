<?php

namespace App\Http\Helpers;

class Colour {
	public static function pantone2hex($pantone) {
		$pantone = strtolower($pantone);
		$pantones = json_decode(file_get_contents(base_path()."/pantones.json"), true);
		if (isset($pantones[$pantone]))
			return $pantones[$pantone];
		else
			return $pantone;
	}
}
