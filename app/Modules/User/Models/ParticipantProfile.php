<?php

namespace App\Modules\User\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;
use App\Modules\Participant\Models\Participant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Models\Relations\ParticipantProfileRelations;
use App\Traits\Walletable\HasManyWallets;

class ParticipantProfile extends Profile
{
    use HasFactory, ParticipantProfileRelations, UuidRouteKeyNameTrait, AddUuidRefAttribute, HasManyWallets;

    protected $table = 'participant_profiles';

    protected $fillable = [
        'profile_id',
        'fundraising_url',
        'slogan',
        'club',
        'emergency_contact_name',
        'emergency_contact_phone',
        'tshirt_size',
        'weekly_physical_activity'
    ];

    protected $casts = [
        'tshirt_size' => ParticipantProfileTshirtSizeEnum::class,
        'weekly_physical_activity' => ParticipantProfileWeeklyPhysicalActivityEnum::class
    ];
}
