<?php

namespace App\Modules\Participant\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Participant\Models\Relations\ParticipantCustomFieldRelations;

class ParticipantCustomField extends Model
{
    use HasFactory, /*SoftDeletes, */ ParticipantCustomFieldRelations, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'participant_custom_fields';

    protected $fillable = [
        'participant_id',
        'event_custom_field_id',
        'value'
    ];

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->attributes['ref'];
    }




}
