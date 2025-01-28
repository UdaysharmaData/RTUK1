<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\Relations\EventCategoryEventThirdPartyRelations;

class EventCategoryEventThirdParty extends Pivot 
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, EventCategoryEventThirdPartyRelations;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'event_category_event_third_party';

    protected $fillable = [
        'event_third_party_id',
        'event_category_id',
        'external_id',
    ];
}
