<?php

namespace App\Modules\User\Models;

use App\Enums\CharityUserTypeEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Modules\Charity\Models\Charity;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CharityUser extends Pivot
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'charity_user';

    /**
     * @var string[]
     */
    protected $fillable = [
        'charity_id',
        'user_id',
        'type'
    ];

    protected $casts = [
        'type' => CharityUserTypeEnum::class
    ];

    /**
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
