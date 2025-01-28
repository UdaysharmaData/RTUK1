<?php

namespace App\Http\Helpers;

use Auth;
use App\Enums\FeeTypeEnum;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;

class EventHelper {

    /**
     * @param  Event        $event
     * @return FeeTypeEnum
     */
    public static function feeType(Event $event, User $user = null): FeeTypeEnum
    {
        $user = $user ?? Auth::user();
        $email = request('email') ?? request('user.email');

        if ($user) {
            $user->loadMissing('profile');

            if ($event->country && $user->profile?->country) {
                if ($event->country != $user->profile?->country) {
                    return FeeTypeEnum::International;
                }
            }
        } else if ($email) {
            $user = User::with('profile')->where('email', $email)->first();

            if ($user) {
                if ($event->country && $user->profile?->country) {
                    if ($event->country != $user->profile?->country) {
                        return FeeTypeEnum::International;
                    }
                }
            }
        }

        return FeeTypeEnum::Local;
	}
}