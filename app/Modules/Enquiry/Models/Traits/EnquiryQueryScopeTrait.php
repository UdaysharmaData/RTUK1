<?php

namespace App\Modules\Enquiry\Models\Traits;

use Auth;
use App\Http\Helpers\AccountType;
use App\Enums\EnquiryStatusEnum;
use App\Enums\CharityUserTypeEnum;
use Illuminate\Database\Eloquent\Builder;

trait EnquiryQueryScopeTrait
{
    /**
     * Scope a query to only include pending or processed enquiries.
     *
     * @param  Builder            $query
     * @param  EnquiryStatusEnum  $value
     * @return Builder
     */
    public function scopeStatus(Builder $query, EnquiryStatusEnum $value): Builder
    {
        switch ($value) {
            case EnquiryStatusEnum::Processed:
                $query = $query->whereNotNull('participant_id');
                break;

            case EnquiryStatusEnum::Pending:
                $query = $query->whereNull('participant_id');
                break;
        }

        return $query;
    }

    /**
     * A query scope to filter enquiries by user access.
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
