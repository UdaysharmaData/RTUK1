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
use Illuminate\Support\Facades\Cache;

class ApiClientCareer extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, SiteIdAttributeGenerator, BelongsToSite, UseSiteGlobalScope;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'description',
        'link',
        'application_closes_at',
        'others'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'active' => 'boolean'
    ];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:1000'],
            'link' => ['nullable', 'string', 'max:200'],
        ]
    ];

    /**
     * @return string
     */
    private static function getCareersCacheKey(): string
    {
        return 'api-client-careers-' . clientId();
    }

    /**
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return mixed
     */
    public static function getCachedCareersCollection(): mixed
    {
        return Cache::rememberForever(self::getCareersCacheKey(), fn() => self::latest()->get());
    }

    /**
     * @return void
     */
    public static function removeCachedCareersCollection(): void
    {
        Cache::has(self::getCareersCacheKey()) && Cache::forget(self::getCareersCacheKey());
    }

    /**
     * @return mixed
     */
    public static function refreshCachedCareersCollection(): mixed
    {
        self::removeCachedCareersCollection();
        return self::getCachedCareersCollection();
    }
}
