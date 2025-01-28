<?php

namespace App\Modules\Enquiry\Models\Traits;

use Auth;
use App\Http\Helpers\AccountType;
use App\Enums\ExternalEnquiryStatusEnum;
use Illuminate\Database\Eloquent\Builder;

trait ExternalEnquiryQueryScopeTrait
{
    /**
     * Scope a query to only include pending or processed enquiries.
     *
     * @param  Builder                    $query
     * @param  ExternalEnquiryStatusEnum  $value
     * @return Builder
     */
    public function scopeStatus(Builder $query, ExternalEnquiryStatusEnum $value): Builder
    {
        switch ($value) {
            case ExternalEnquiryStatusEnum::Processed:
                $query = $query->whereNotNull('participant_id');

                break;

            case ExternalEnquiryStatusEnum::Pending:
                $query = $query->whereNull('participant_id');

                break;
        }

        return $query;
    }

    /**
     * A query scope to filter external enquiries by user access.
     * 
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFilterByAccess(Builder $query): Builder
    {
        if (AccountType::isAccountManager()) {
            $query = $query->whereHas('charity.charityManager', function ($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        if (AccountType::isCharityOwnerOrCharityUser()) {
            $query = $query->whereHas('charity.users', function ($query) {
                $query->where('user_id', Auth::user()->id)
                    ->where(function($query) {
                        $query->where('type', CharityUserTypeEnum::Owner)
                            ->orWhere('type', CharityUserTypeEnum::User);
                    });
            });
        }

        return $query;
    }
}
