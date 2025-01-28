<?php

namespace App\Models\Traits;

use Auth;
use App\Http\Helpers\AccountType;
use App\Enums\CharityUserTypeEnum;
use App\Modules\Charity\Models\Charity;
use Illuminate\Database\Eloquent\Builder;

trait InvoiceQueryScopeTrait
{
    /**
     * A query scope to filter participants by user access.
     * 
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFilterByAccess(Builder $query): Builder
    {
        if (AccountType::isAdmin()) { // Ensure the admins only have access to the invoices of their sites
            $query = $query->whereHas('site', function($query) {
                $query->hasAccess()
                    ->makingRequest();
            });
        }

        if (AccountType::isAccountManager()) {
            $query = $query->whereHasMorph(
                'invoiceable',
                [Charity::class],
                function($query) {
                    $query = $query->whereHas('charityManager', function($query) {
                        $query->where('user_id', Auth::user()->id);
                    });
                }
            );
        }

        if (AccountType::isCharityOwnerOrCharityUser()) {
            $query = $query->whereHasMorph(
                'invoiceable',
                [Charity::class],
                function ($query) {
                    $query->whereHas('users', function($query) {
                        $query->where('user_id', Auth::user()->id)
                            ->where(function($query) {
                                $query->where('type', CharityUserTypeEnum::Owner)
                                    ->orWhere('type', CharityUserTypeEnum::User);
                            });
                    });
                }
            );
        }

        return $query;
    }
}
