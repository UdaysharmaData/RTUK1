<?php

namespace App\Models;

use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\User;
use App\Traits\AddUuidRefAttribute;
use App\Traits\AuthorIdAttributeGenerator;
use App\Traits\BelongsToAuthor;
use App\Traits\BelongsToSite;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\Uploadable\HasManyUploads;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model implements CanUseCustomRouteKeyName, CanHaveManyUploadableResource
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteIdAttributeGenerator,
        AuthorIdAttributeGenerator,
        UseSiteGlobalScope,
        BelongsToSite,
        HasManyUploads,
        BelongsToAuthor;

    /**
     * @var string[]
     */
    protected $with = [
        'uploads'
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'audience_id',
        'subject',
        'body',
        'scheduled_at'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'scheduled_at' => 'immutable_date'
    ];

    /**
     * @return BelongsTo
     */
    public function audience(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Audience::class);
    }

    /**
     * @return BelongsToMany
     */
    public function audiences(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Audience::class);
    }


    /**
     * @return HasMany
     */
    public function attachableEntities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AttachableEntity::class);
    }
}
