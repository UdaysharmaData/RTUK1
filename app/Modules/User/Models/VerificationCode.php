<?php

namespace App\Modules\User\Models;

use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use function now;

class VerificationCode extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteIdAttributeGenerator,
        UseSiteGlobalScope,
        BelongsToSite;

    const VALIDITY_IN_MINUTES = 5;

    /**
     * @var string[]
     */
    protected $fillable = [
        'type',
        'is_active'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return now() >= $this->attributes['expires_at'];
    }

    /**
     * @return Carbon
     */
    public function expiryDate(): Carbon
    {
        return $this->attributes['created_at']->addMinutes(10);
    }

    /**
     * @param int $minutesFromNow
     * @return Carbon
     */
    public static function setExpiration(int $minutesFromNow = self::VALIDITY_IN_MINUTES): Carbon
    {
        return now()->addMinutes($minutesFromNow);
    }

    /**
     * @throws Exception
     * @return string
     */
    public static function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param $query
     * @return Builder
     */
    public function scopeActive($query): Builder
    {
        return $query->where('expires_at', '>=', now());
    }

    /**
     * @return string
     */
    public static function getValidityMessage(): string
    {
        $minutes = VerificationCode::VALIDITY_IN_MINUTES;
        $suffix = Str::plural('minute', $minutes);

        return "(Note: Code is only valid for <strong>$minutes $suffix</strong>.)";
    }
}
