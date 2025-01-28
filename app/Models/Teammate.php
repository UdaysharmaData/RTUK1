<?php

namespace App\Models;

use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\Uploadable\HasOneUpload;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teammate extends Model implements CanUseCustomRouteKeyName, CanHaveUploadableResource
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, SiteIdAttributeGenerator, UseSiteGlobalScope, HasOneUpload, BelongsToSite;

    /**
     * @var string[]
     */
    protected $fillable = ['name', 'title'];

    /**
     * @var string[]
     */
    protected $with = ['upload'];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'name' => ['required', 'string', 'max:200'],
            'title' => ['required', 'string', 'max:200']
        ]
    ];
}
