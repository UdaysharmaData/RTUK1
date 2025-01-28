<?php

namespace App\Modules\Charity\Models;

use Carbon\Carbon;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CharityMembershipTypeEnum;
use App\Traits\InvoiceItemable\HasOneInvoiceItem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\InvoiceItemables\CanHaveInvoiceItemableResource;

class CharityMembership extends Model implements CanHaveInvoiceItemableResource
{
    use HasFactory, HasOneInvoiceItem, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'charity_memberships';

    protected $fillable = [
        'charity_id',
        'type', // the type of membership
        'status',
        'use_new_membership_fee',
        'renewed_on',
        // 'start_date', // moved to an accessor
        'expiry_date'
    ];

    protected $casts = [
        'type' => CharityMembershipTypeEnum::class,
        'status' => 'boolean',
        'use_new_membership_fee' => 'boolean',
        'renewed_on' => 'date',
        'expiry_date' => 'date'
    ];

    protected $appends = [
        'start_date',
        'membership_fee',
    ];

    const ACTIVE = 1; // Active membership

    const INACTIVE = 0; // InActive membership

    /**
     * Get the charity that owns the membership.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the start_date
     *
     * @return Attribute
     */
    protected function startDate(): Attribute
    {
        return Attribute::make(
            get: function ($value) {

                $startDate = Carbon::parse($this->expiry_date);

                switch ($this->type) {
                    case CharityMembershipTypeEnum::Classic:
                    case CharityMembershipTypeEnum::Premium:
                    case CharityMembershipTypeEnum::Partner:
                        $startDate = $startDate->subYear();
                        break;

                    case CharityMembershipTypeEnum::TwoYear:
                        $startDate = $startDate->subYears(2);
                        break;
                }

                return $startDate;
            },
        );
    }

    /**
     * Get the membership fee based on the type.
     * 
     * @return int|null
     */
    public function getMembershipFeeAttribute(): int|null
    {
        // $fee = null;

        // $settings = Setting::all();

        // switch ($this->type) {
        //     case 'classic':
        //         $fee = $this->use_new_membership_fee ? $settings[0]->new_classic_renewal : $settings[0]->classic_renewal;
        //         break;

        //     case 'premium':
        //         $fee = $this->use_new_membership_fee ? $settings[0]->new_premium_renewal : $settings[0]->premium_renewal;
        //         break;

        //     case 'two_year':
        //         $fee = $this->use_new_membership_fee ? $settings[0]->new_two_year_renewal : $settings[0]->two_year_renewal;
        //         break;

        //     case 'partner':
        //         $fee = $settings[0]->partner_renewal;
        //         break;
        // }

        // return $fee;

            return 200;
    }

}
