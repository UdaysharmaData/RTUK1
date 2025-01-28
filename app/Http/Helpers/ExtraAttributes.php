<?php

namespace App\Http\Helpers;

class ExtraAttributes {

	public static function get(): array
	{
		$extraAttributes = request('extra_attributes') ?? [];

		return $extraAttributes;
	}
}