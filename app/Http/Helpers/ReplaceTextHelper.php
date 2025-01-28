<?php
namespace App\Http\Helpers;

use App\Http\Helpers\AccountType;

class ReplaceTextHelper {

    public static function participantOrEntry($count = 1)
     {
        if (AccountType::isParticipant()) {
            return  $count > 1 ? 'entries' : 'entry';
        } else {
            return $count > 1 ? 'participants' : 'participant';
        }
    }
}