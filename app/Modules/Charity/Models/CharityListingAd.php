<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\Uploadable\HasOneUpload;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CharityListingAdTypeEnum;
use App\Enums\CharityListingAdPositionEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveUploadableResource;

class CharityListingAd extends Model implements CanHaveUploadableResource
{
    use HasFactory, HasOneUpload, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'charity_listing_ads';

    protected $fillable = [
        'charity_listing_id',
        'key',
        'position',
        'type',
        'link'
    ];

    protected $casts = [
        'position' => CharityListingAdPositionEnum::class,
        'type' => CharityListingAdTypeEnum::class
    ];

    /**
     * Get the charity listing associated with the ad
     * @return BelongsTo
     */
    public function charityListing(): BelongsTo
    {
        return $this->belongsTo(CharityListing::class);
    }
}
