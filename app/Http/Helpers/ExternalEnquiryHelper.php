<?php

namespace App\Http\Helpers;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Participant\Models\Participant;

class ExternalEnquiryHelper {

    /**
     * @param  Participant      $participant
     * @param  ExternalEnquiry  $enquiry
     * @param  array            $extraData
     * @return Model
     */
    public static function createParticipantExtraProfile(Participant $participant, ExternalEnquiry $enquiry, array $extraData): Model
    {
        return $participant->participantExtra()->firstOrCreate([
            'first_name' => $enquiry->first_name,
            'last_name' => $enquiry->last_name,
            'dob' => $enquiry->dob,
            'phone' => $enquiry->phone,
            'gender' => $enquiry->gender,
            'ethnicity' => $extraData['ethnicity'] ?? null,
            'club' => $extraData['club'] ?? null,
            'weekly_physical_activity' => $extraData['weekly_physical_activity'] ?? null
        ]);
    }

    /**
     * Checks if it is an extra participant (having profile details different from parent record)
     * 
     * @param  Participant      $participant
     * @param  ExternalEnquiry  $enquiry
     * @return bool
     */
    public static function isProfileDifferentFromParentRecordProfile(Participant $participant, ExternalEnquiry $enquiry): bool
    {
        $participant = Participant::with('user.profile')->where('user_id', $participant->user_id)
            ->where('event_event_category_id', $participant->event_event_category_id)
            ->latest()
            ->first();

        return ! static::compareProfiles($participant, $enquiry);
    }

    /**
     * Compare parent (user) and child (external enquiry) participant profile
     * 
     * @param  Participant      $parent
     * @param  ExternalEnquiry  $child
     * @return bool                     // returns true when there is a match.
     */
    public static function compareProfiles(Participant $parent, ExternalEnquiry $child): bool
    {
        $matches = true;

        if ($parent->user->first_name && $child->first_name && ($parent->user->first_name != $child->first_name)) {
            $matches *= false;
        } else if ($parent->user->last_name && $child->last_name && ($parent->user->last_name != $child->last_name)) {
            $matches *= false;
        } else if ($parent->user->phone && $child->phone && ($parent->user->phone != $child->phone)) {
            $matches *= false;
        } else if ($parent->user->profile?->dob && $child->dob && ($parent->user->profile->dob != $child->dob)) {
            $matches *= false;
        } else if ($parent->user->profile?->gender && $child->gender && ($parent->user->profile->gender != $child->gender)) {
            $matches *= false;
        }

        return $matches;
    }
}