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

class ClientEnquiry extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, SiteIdAttributeGenerator, UseSiteGlobalScope, BelongsToSite;

    /**
     * @var string[]
     */
    protected $fillable = ['enquiry_type', 'full_name', 'email', 'message'];

    /**
     * default validation rules
     */
    const RULES = [
        'create_or_update' => [
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:1000']
        ]
    ];
}
