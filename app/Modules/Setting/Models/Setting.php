<?php

namespace App\Modules\Setting\Models;

use App\Traits\AddUuidRefAttribute;
use App\Modules\Setting\Models\Site;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Socialable\HasManySocials;
use App\Modules\Setting\Models\SettingCustomField;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Socialables\CanHaveManySocialableResource;

class Setting extends Model implements CanHaveManySocialableResource
{
    use HasFactory, HasManySocials, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'settings';

    protected $fillable = [
        'site_id',
    ];

    /**
     * Get the site associated with the setting.
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the setting custom fields associated with the setting.
     *
     * @return HasMany
     */
    public function settingCustomFields(): HasMany
    {
        return $this->hasMany(SettingCustomField::class);
    }
}
