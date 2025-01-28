<?php

namespace App\Models;

use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\User;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaLibrary extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, SiteIdAttributeGenerator, BelongsToSite, UseSiteGlobalScope;

    /**
     * @var string[]
     */
    protected $fillable = ['name', 'description'];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500']
        ]
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    /**
     * @return MorphTo
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo('mediable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function collections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MediaLibraryCollection::class, 'media_library_id');
    }

    /**
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
