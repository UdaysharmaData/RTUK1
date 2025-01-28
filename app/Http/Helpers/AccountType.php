<?php

namespace App\Http\Helpers;

use Auth;

// use App\Models\RaceResult;

class AccountType {

	public static function isCharityOwner() {
		if (Auth::check()) {
			return Auth::user()->isCharityOwner();
		}

		return false;
	}

	public static function isCharityUser() {
		if (Auth::check()) {
			return Auth::user()->isCharityUser();
		}

		return false;
	}

	public static function isCharityOwnerOrCharityUser() {
		if (Auth::check()) {
			if (Auth::user()->isCharityOwner() || Auth::user()->isCharityUser()) {
				return true;
			}
		}

		return false;
	}

	public static function isAdmin() {
		if(Auth::check()) {
			if (Auth::user()->isAdmin() || Auth::user()->isDeveloper()) {
				return true;
			}
		}

		return false;
	}

	public static function isGeneralAdmin() {
		if(Auth::check()) {
			if (Auth::user()->isGeneralAdmin()) {
				return true;
			}
		}

		return false;
	}

	public static function isDeveloper() {
		if(Auth::check()) {
			if (Auth::user()->isDeveloper()) {
				return true;
			}
		}

		return false;
	}

	public static function isAdminOrAccountManagerOrDeveloper() {
		if(Auth::check()) {
			if (Auth::user()->isAdmin() || Auth::user()->isAccountManager() || Auth::user()->isDeveloper()) {
				return true;
			}
		}

		return false;
	}

	public static function isAdminOrCharityOwnerOrCharityUserOrDeveloper() {
		if(Auth::check()) {
			if (Auth::user()->isAdmin() || Auth::user()->isCharityOwner() || Auth::user()->isCharityUser() || Auth::user()->isDeveloper()) {
				return true;
			}
		}

		return false;
	}

	public static function isAdminOrAccountManagerOrCharityOwnerOrCharityUserOrDeveloper() {
		if(Auth::check()) {
			if (Auth::user()->isAdmin() || Auth::user()->isAccountManager() ||  Auth::user()->isCharityOwner() || Auth::user()->isCharityUser() || Auth::user()->isDeveloper()) {
				return true;
			}
		}

		return false;
	}

	public static function isAccountManagerOrCharityOwnerOrCharityUser() {
		if (Auth::check()) {
			if (Auth::user()->isAccountManager() || Auth::user()->isCharityOwner() || Auth::user()->isCharityUser()) {
				return true;
			}
		}

		return false;
	}

	public static function isPartner() {
		if(Auth::check()) {
			if(Auth::user()->role && Auth::user()->role->name == 'partner') {
				return true;
			}
		}

		return false;
	}

	public static function isParticipant() {
		if (Auth::check()) {
			if (Auth::user()->isParticipant()) {
				return true;
			}
		}

		return false;
	}

	public static function isAccountManager() {
		if (Auth::check()) {
			return Auth::user()->isAccountManager();
		}

		return false;
	}

	public static function isEventManager() {
		if (Auth::check()) {
			return Auth::user()->isEventManager();
		}

		return false;
	}

	public static function isCorporate() {
		if(Auth::check()) {
			if(Auth::user()->role && Auth::user()->role->name == 'corporate') {
				return true;
			}
		}

		return false;
	}

	public static function isRunThroughData() {
		if(Auth::check()) {
			if(Auth::user()->role && Auth::user()->role->name == 'runthrough_data') {
				return true;
			}
		}

		return false;
	}

	public static function isContentManager() {
		if(Auth::check()) {
			if(Auth::user()->role && Auth::user()->role->name == 'content_manager') {
				return true;
			}
		}

		return false;
	}
}
