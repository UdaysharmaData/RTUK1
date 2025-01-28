<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Modules\Partner\Models\Partner;
use App\Traits\Uploadable\HasOneUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PartnerPackage extends Model implements CanHaveUploadableResource
{
    use HasFactory, HasOneUpload, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'partner_packages';

    protected $fillable = [
        'partner_id',
        'name',
        'price',
        'quantity',
        'start_date',
        'end_date',
        'renewal_date',
        'description',
        'price_commission',
        'renewal_commission',
        'new_business_commission',
        'partner_split_after_commission',
        'rfc_split_after_commission',
        'renewed_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_date' => 'date',
    ];

    /**
     * The partner that owns the package.
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the charity partner packages associated with the partner package.
     * @return hasMany
     */
    public function charityPartnerPackages(): hasMany
    {
        return $this->hasMany(CharityPartnerPackage::class);
    }

    /**
     * Get the charities that have subscribed to the partner package.
     * @return BelongsToMany
     */
    public function charities(): BelongsToMany
    {
        return $this->belongsToMany(Charity::class)->using(CharityPartnerPackage::class);
    }
}
