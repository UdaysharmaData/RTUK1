<?php

namespace App\Models;

use App\Enums\AudienceSourceEnum;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\Role;
use App\Services\SoftDeleteable\Contracts\SoftDeleteableContract;
use App\Services\SoftDeleteable\Traits\ActionMessages;
use App\Traits\AddActiveRoleIdAttribute;
use App\Traits\AddAuthorIdAttribute;
use App\Traits\BelongsToAuthor;
use App\Traits\FilterableListQueryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\SiteIdAttributeGenerator;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UseSiteGlobalScope;
use App\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audience extends Model implements CanUseCustomRouteKeyName, SoftDeleteableContract
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        AddAuthorIdAttribute,
        AddActiveRoleIdAttribute,
        SiteIdAttributeGenerator,
        UseSiteGlobalScope,
        BelongsToSite,
        FilterableListQueryScope,
        BelongsToAuthor,
        SoftDeletes,
        ActionMessages;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'source'
    ];

    protected $casts = [
        'source' => AudienceSourceEnum::class,
    ];

    /**
     * @var string[]
     */
    public static $actionMessages = [
        'force_delete' => 'Deleting an audience permanently will unlink it from mailing lists and other associated services within the platform.'
    ];

    /**
     * @return HasMany
     */
    public function mailingLists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MailingList::class);
    }

    /**
     * Author's role at the point of audience creation
     * @return BelongsTo
     */
    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
