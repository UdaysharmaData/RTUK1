<?php

namespace App\Models;

use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddUuidRefAttribute;
use App\Traits\ClientIdAttributeGenerator;
use App\Traits\UseClientGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Passport\Token;

class ConnectedDevice extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        ClientIdAttributeGenerator,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        UseClientGlobalScope;

    /**
     * @var string[]
     */
    protected $fillable = [
        'finger_print',
        'device',
        'platform',
        'browser',
        'is_bot',
        'ip',
        'user_id'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_bot' => 'boolean'
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'api_client_id',
        'finger_print',
        'ip'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'active'
    ];

    /**
     * Get connection status of device
     *
     * @return Attribute
     */
    protected function active(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->tokens()
                ->where('revoked', false)
                ->whereDate('expires_at', '>', now())
                ->exists()
        );
    }

    /**
     * @return HasMany
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * @return bool|null
     * @throws \Throwable
     */
    public function remove(): ?bool
    {
        return $this->deleteOrFail();
    }
}
