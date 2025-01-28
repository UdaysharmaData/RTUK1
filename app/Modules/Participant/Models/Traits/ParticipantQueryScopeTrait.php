<?php

namespace App\Modules\Participant\Models\Traits;

use Auth;
use App\Enums\InvoiceStatusEnum;
use App\Http\Helpers\AccountType;
use App\Enums\CharityUserTypeEnum;
use App\Enums\InvoiceItemStatusEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Modules\Charity\Models\Charity;
use App\Modules\Corporate\Models\Corporate;
use Illuminate\Database\Eloquent\Builder;

trait ParticipantQueryScopeTrait
{
    /**
     * A query scope to filter participants by user access.
     * 
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFilterByAccess(Builder $query): Builder
    {
        return $query->when(AccountType::isCharityOwnerOrCharityUser(),
            fn ($query) => $query->whereHas('charity.users', function ($query) {
                $query->where('user_id', Auth::user()->id)
                    ->where(function($query) {
                        $query->where('type', CharityUserTypeEnum::Owner)
                            ->orWhere('type', CharityUserTypeEnum::User);
                    });
            })
        )->when(AccountType::isAccountManager(), fn ($query) => $query->whereHas('charity.charityManager', function ($query) {
            $query->where('user_id', Auth::user()->id);
        }))->when(AccountType::isEventManager(), fn ($query) => $query->whereHas('event.eventManagers', function ($query) {
            $query->where('user_id', Auth::user()->id);
        }))->when(AccountType::isParticipant(), fn ($query) => $query->where('user_id', Auth::user()->id));

        // if (AccountType::isCorporate()) {
        //     $participant = $participant->whereHas('corporate', function ($query) {
        //         $query->whereHas('user', function ($query) {
        //             $query->where('id', Auth::user()->id);
        //         });
        //     });
        // }
    }

    /**
     * A query scope to filter participants by completed registration.
     * // TODO: Make this method take a bool param to return either completed or incomplete registrations
     * 
     * @param  Builder  $query
     * @param  Charity|null    $charity
     * @param  Corporate|null  $corporate
     * @return Builder
     */
    public function scopeCompletedRegistration(Builder $query, ?Charity $charity = null, ?Corporate $corporate = null): Builder
    {
        return $query->where('status', ParticipantStatusEnum::Complete)
            ->when(
                $charity,
                fn ($query) => $query->where('charity_id', $charity->id),
            )->when(
                $corporate,
                fn ($query) => $query->where('corporate_id', $corporate->id),
            );
    }

    /**
     * A query scope to filter participants by completed registration.
     * 
     * TODO: @tsaffi - Update this implementation
     * 
     * @param  Builder         $query
     * @param  Charity|null    $charity
     * @param  Corporate|null  $corporate
     * @return Builder
     */
    public function scopeConsideredAmongCompletedRegistration(Builder $query, ?Charity $charity = null, ?Corporate $corporate = null): Builder
    {
        $query = $query->whereNot('status', ParticipantStatusEnum::Transferred)
            ->where(function($query) {
                $query->whereIn('status', [ParticipantStatusEnum::Complete/*, ParticipantStatusEnum::Clearance*/])
                    ->orWhereHas('invoiceItem', function ($query) {
                        $query->where('status', InvoiceItemStatusEnum::Paid)
                            ->whereHas('invoice', function ($q1) {
                                $q1->whereHas('transactions', function ($q2) {
                                    $q2->where(function ($q3) {
                                        $q3->whereHas('externalTransaction', function ($q4) {
                                            $q4->whereNotNull('charge_id');
                                        })->orHas('internalTransactions');
                                    });
                                });
                        });
                    })->orWhere(function ($query) {
                        $query->where('waive', ParticipantWaiveEnum::Completely)
                            ->where('waiver', ParticipantWaiverEnum::Partner);
                    });
            });

        if ($charity) {
            $query = $query->where('charity_id', $charity->id);
        } else if ($corporate) {
            $query = $query->where('corporate_id', $corporate->id);
        }

        return $query;
    }

    /**
     * A query scope to filter participants by uncompleted registration.
     * 
     * TODO: @tsaffi - Update this implementation
     * 
     * @param  Builder         $query
     * @param  Charity|null    $charity
     * @param  Corporate|null  $corporate
     * @return Builder
     */
    public function scopeUncompletedRegistration(Builder $query, ?Charity $charity = null, ?Corporate $corporate = null): Builder
    {
        return $query->whereNot('status', ParticipantStatusEnum::Complete)
            ->when(
                $charity,
                fn ($query) => $query->where('charity_id', $charity->id),
            )->when(
                $corporate,
                fn ($query) => $query->where('corporate_id', $corporate->id),
            );
    }
}
