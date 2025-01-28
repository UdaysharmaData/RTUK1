<?php

namespace App\Modules\Participant\Models;

use App\Enums\GenderEnum;
use App\Traits\AddUuidRefAttribute;
use App\Enums\ProfileEthnicityEnum;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;
use App\Modules\Participant\Models\Relations\ParticipantExtraRelations;

class ParticipantExtra extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, ParticipantExtraRelations;

    protected $table = 'participant_extras';

    protected $fillable = [
        'participant_id',
        'first_name',
        'last_name',
        'dob',
        'phone',
        'gender',
        'ethnicity',
        'weekly_physical_activity',
        'speak_with_coach',
        'hear_from_partner_charity',
        'distance_like_to_run_here',
        'race_pack_posted',
        'club',
        'reason_for_participating'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'gender' => GenderEnum::class,
        'ethnicity' => ProfileEthnicityEnum::class,
        'weekly_physical_activity' => ParticipantProfileWeeklyPhysicalActivityEnum::class,
        'speak_with_coach' => 'boolean',
        'hear_from_partner_charity' => 'boolean'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'full_name',
    ];

    /**
     * @return Attribute
     */
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "$this->first_name $this->last_name",
        );
    }

    public function salutationName(): Attribute
    {
        return Attribute::make(
            get: fn() => ucfirst($this->first_name) ?: ucfirst($this->last_name)?: ''
        );
    }
}
