<?php

namespace App\Modules\Participant\Models;

use App\Traits\SiteTrait;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ParticipantActionTypeEnum;
use App\Traits\UseDynamicallyAppendedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\Participant\Models\Relations\ParticipantActionRelations;

class ParticipantAction extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        SiteTrait,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        ParticipantActionRelations,
        UseDynamicallyAppendedAttributes;

    protected $table = 'participant_actions';

    protected $fillable = [
        'participant_id',
        'user_id',
        'role_id',
        'type'
    ];

    protected $casts = [
        'type' => ParticipantActionTypeEnum::class
    ];

    /**
     * Get the description
     *
     * @return Attribute
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                switch ($this->type) {
                    case ParticipantActionTypeEnum::Added:
                        $value .= "Added by {$this->participant->user->full_name} via " . static::getSite()?->name . "(" . $this->participant->added_via?->formattedName() . ")";
                        break;
                    case ParticipantActionTypeEnum::Deleted:
                        $value .= "Deleted by {$this->participant->user->full_name} on " . $this->created_at?->format("M d, Y");
                        break;
                    case ParticipantActionTypeEnum::Restored:
                        $value .= "Restored by {$this->participant->user->full_name} on" . $this->created_at?->format("M d, Y");
                        break;
                    default:
                        $value = $this->participant->added_via?->formattedName();
                        break;
                }

                return $value;
            },
        );
    }
}
