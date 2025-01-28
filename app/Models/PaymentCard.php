<?php

namespace App\Models;

use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\User;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PaymentCard extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $fillable = [
        'card_name',
        'card_number',
        'expiry_date',
    ];

    protected $hidden = [
        'cvv',
        'card_number'
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'expiry_date'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'has_expired',
        'ends_with'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Attribute
     */
    protected function expiryDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('m/y'),
            set: fn ($value) => Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d')
        );
    }

    /**
     * @return Attribute
     */
    protected function hasExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::now()->format('m/y')
                >= Carbon::createFromFormat('m/y', $this->expiry_date)
                    ->format('m/y')
        );
    }

    /**
     * @return Attribute
     */
    protected function endsWith(): Attribute
    {
        return Attribute::make(
            get: function () {
                $value = $this->card_number;
                $partial = substr($value, 12, strlen($value));
                return "************$partial";
            }
        );
    }
}
