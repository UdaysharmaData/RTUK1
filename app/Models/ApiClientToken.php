<?php

namespace App\Models;

use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddUuidRefAttribute;
use App\Traits\ClientIdAttributeGenerator;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiClientToken extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, ClientIdAttributeGenerator, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * duration set for token validity
     */
    const TOKEN_DURATION = [
        'minutes' => 30
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'token',
        'expires_at',
        'is_revoked'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_revoked' => 'boolean',
        'expires_at' => 'datetime'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_expired'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function apiClient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Determine if the token is expired.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function isExpired(): Attribute
    {
        return new Attribute(
            get: fn ($value, $attributes) => $attributes['expires_at'] <= now()
        );
    }
}
