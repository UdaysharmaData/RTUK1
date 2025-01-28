<?php

namespace App\Http\Helpers;

use Validator;

class EmailAddressHelper {

	/**
	 * Validate email address
	 * 
	 * @return bool
	 */
	public static function isValid($email): bool
	{
		return Validator::make(['email' => $email], ['email' => 'email'])->passes();
	}
}
