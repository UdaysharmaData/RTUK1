<?php

namespace App\Modules\User\Models;

use App\Services\TwoFactorAuth\Concerns\HandleSafeDevice;
use App\Services\TwoFactorAuth\Concerns\HandlesCodes;
use App\Services\TwoFactorAuth\Concerns\HandlesRecoveryCodes;
use App\Services\TwoFactorAuth\Concerns\SerializesSharedSecret;
use App\Services\TwoFactorAuth\Contracts\TwoFactorOtpCode;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ParagonIE\ConstantTime\Base32;

class TwoFactorAuthentication extends model implements TwoFactorOtpCode
{
    use HandlesCodes;
    use HandlesRecoveryCodes;
    use HandleSafeDevice;
    use SerializesSharedSecret;
    use HasFactory;

    protected $table = 'two_factor_authentications';

    protected $fillable = [
        'label',
        'digits',
        'seconds',
        'window',
        'algorithm',
    ];

    protected $casts = [
        'shared_secret'               => 'encrypted',
        'digits'                      => 'int',
        'seconds'                     => 'int',
        'window'                      => 'int',
        'recovery_codes'              => 'encrypted:collection',
        'safe_devices'                => 'collection',
        'enabled_at'                  => 'datetime',
        'recovery_codes_generated_at' => 'datetime',
    ];

    /**
     * The user that uses Two-Factor authentication
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function setAlgorithmAttribute($value)
    {
        $this->attributes['algorithm'] = strtolower($value);
    }

    /**
     * Creates a new Random Secret.
     *
     * @return string
     * @throws Exception|Exception
     */
    public static function generateRandomSecret(): string
    {
        return Base32::encodeUpper(
            random_bytes(config('two-factor.secret_length'))
        );
    }

    /**
     * Flushes all authentication data and cycles the Shared Secret.
     *
     * @return $this
     * @throws Exception
     */
    public function flushAuth(): static
    {
        $this->recovery_codes_generated_at = null;
        $this->safe_devices = null;

        $this->attributes = array_merge($this->attributes, config('two-factor.totp'));

        $this->shared_secret = static::generateRandomSecret();
        $this->recovery_codes = null;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function flushSharedSecret(): static
    {
        $this->shared_secret = static::generateRandomSecret();
        return  $this;
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toUri(), $options);
    }

}
