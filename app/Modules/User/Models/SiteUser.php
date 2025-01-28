<?php

namespace App\Modules\User\Models;

use App\Enums\SiteUserStatus;
use App\Traits\AddUuidRefAttribute;
use App\Modules\Setting\Models\Site;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SiteUser extends Pivot
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'site_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'site_id',
        'user_id',
        'status'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'status' => SiteUserStatus::class
    ];

    /**
     * Get the site.
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the user.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
