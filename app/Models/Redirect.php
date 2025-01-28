<?php

namespace App\Models;

use App\Contracts\FilterableListQuery;
use App\Enums\RedirectHardDeleteStatusEnum;
use App\Enums\RedirectSoftDeleteStatusEnum;
use App\Enums\RedirectStatusEnum;
use App\Enums\RedirectTypeEnum;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Services\SoftDeleteable\Contracts\SoftDeleteableContract;
use App\Services\SoftDeleteable\Traits\ActionMessages;
use App\Traits\AddUuidRefAttribute;
use App\Traits\FilterableListQueryScope;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Redirect extends Model implements CanUseCustomRouteKeyName, SoftDeleteableContract, FilterableListQuery
{
    use HasFactory,
        SoftDeletes,
        UuidRouteKeyNameTrait,
        SiteIdAttributeGenerator,
        AddUuidRefAttribute,
        UseSiteGlobalScope,
        ActionMessages,
        FilterableListQueryScope;

    protected $fillable = [
        'site_id',
        'redirectable_id',
        'redirectable_type',
        'model',
        'target_url',
        'redirect_url',
        'soft_delete',
        'hard_delete',
        'type',
        'is_active',
        'target_path',
        'is_process',
        'resync_again',
    ];

    protected $casts = [
        'soft_delete' => RedirectSoftDeleteStatusEnum::class,
        'hard_delete' => RedirectHardDeleteStatusEnum::class,
        'type' => RedirectTypeEnum::class,
        'model' => 'object',
        'is_active' => 'boolean',
    ];

    /**
     * @var string[]
     */
    public static array $actionMessages = [
        'force_delete' => 'Deleting a redirect settings for any resource may disrupt the SEO redirection mechanism in place for the resource. Are you sure you want to proceed?',
    ];
}
