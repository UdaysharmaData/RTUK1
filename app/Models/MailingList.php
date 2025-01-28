<?php

namespace App\Models;

use App\Services\SoftDeleteable\Contracts\SoftDeleteableContract;
use App\Services\SoftDeleteable\Traits\ActionMessages;
use App\Traits\BelongsToSite;
use App\Traits\BelongsToAuthor;
use App\Traits\UseSiteGlobalScope;
use App\Traits\AddUuidRefAttribute;
use App\Traits\AddAuthorIdAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SiteIdAttributeGenerator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailingList extends Model implements CanUseCustomRouteKeyName, SoftDeleteableContract
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        AddAuthorIdAttribute,
        SiteIdAttributeGenerator,
        UseSiteGlobalScope,
        BelongsToSite,
        BelongsToAuthor,
        SoftDeletes,
        ActionMessages;

    /**
     * @var string[]
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone'
    ];

    /**
     * @var string[]
     */
    public static $actionMessages = [
        'force_delete' => 'Permanently deleting a mailing list entry will unlink it from audiences and other associated services within the platform.'
    ];

    /**
     * @return BelongsTo
     */
    public function audience(): BelongsTo
    {
        return $this->belongsTo(Audience::class);
    }
}
