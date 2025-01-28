<?php

namespace App\Models;

use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachableEntity extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteIdAttributeGenerator,
        UseSiteGlobalScope,
        BelongsToSite;


    /**
     * @var string[]
     */
    protected $fillable = [
        'message_id'
    ];
}
