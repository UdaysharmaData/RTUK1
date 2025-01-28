<?php

namespace App\Modules\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\SlugTrait;
use App\Traits\BelongsToSite;
use App\Traits\HasManyEvents;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\FilterableListQueryScope;
use App\Contracts\ConfigurableEventProperty;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Traits\ConfigurableEventPropertyNameSlugAttribute;
use App\Traits\Drafts\DraftTrait;

class Sponsor extends Model implements ConfigurableEventProperty
{
    use HasFactory,
        SoftDeletes,
        SlugTrait,
        DraftTrait,
        AddUuidRefAttribute,
        DraftTrait,
        UuidRouteKeyNameTrait,
        FilterableListQueryScope,
        ConfigurableEventPropertyNameSlugAttribute,
        UseDynamicallyAppendedAttributes,
        BelongsToSite,
        HasManyEvents;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'site_id',
        'description'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the sponsor permanently will unlink it from events and others. This action is irreversible.'
    ];
}
