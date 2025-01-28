<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Modules\Charity\Models\Charity;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ListingPageCharityTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Replaces ListingsPageCharity
 * The model contains preset primary and secondary partners (charities) used when creating a charity listing through the listing pages.
 */
class ListingPageCharity extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'listing_page_charities';

    protected $fillable = [
        'charity_id',
        'type',
    ];

    protected $casts = [
        'type' => ListingPageCharityTypeEnum::class
    ];

    /**
     * Get the charity.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }
}
