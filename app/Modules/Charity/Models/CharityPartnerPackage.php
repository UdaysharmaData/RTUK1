<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Enums\CharityPartnerPackageStatusEnum;
use App\Traits\InvoiceItemable\HasOneInvoiceItem;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\InvoiceItemables\CanHaveInvoiceItemableResource;

/**
 * Replaces AssignPartnerPackage model
 */
class CharityPartnerPackage extends Pivot implements CanHaveInvoiceItemableResource
{
    use HasFactory, HasOneInvoiceItem, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'charity_partner_package';

    protected $fillable = [
        'partner_package_id',
        'charity_id',
        'contract_id',
        'status',
    ];

    protected $casts = [
        'status' => CharityPartnerPackageStatusEnum::class
    ];

    /**
     * The partner package.
     * @return BelongsTo
     */
    public function partnerPackage(): BelongsTo
    {
        return $this->belongsTo(PartnerPackage::class);
    }

    /**
     * The charity to whom the package has been assigned.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * The contract
     * @return BelongsTo
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
