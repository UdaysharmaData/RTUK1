<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\Relations\EventThirdPartyRelations;

class EventThirdParty extends Model 
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, EventThirdPartyRelations;

    protected $table = 'event_third_parties';

    protected $fillable = [
        'event_id',
        'external_id',
        'partner_channel_id',
        'occurrence_id'
    ];
}
