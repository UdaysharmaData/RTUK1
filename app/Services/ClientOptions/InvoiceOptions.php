<?php

namespace App\Services\ClientOptions;

use Auth;
use App\Http\Helpers\AccountType;
use Illuminate\Support\Facades\Cache;

use App\Models\Invoice;
use App\Models\InvoiceItem;

use App\Enums\BoolYesNoEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\InvoicesListOrderByFieldsEnum;

class InvoiceOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        $userId = AccountType::isParticipant() ? Auth::user()->id : null;
        $userId = AccountType::isCharityOwnerOrCharityUser() ? Auth::user()->id : null;

        return Cache::remember("invoices-stats-year-filter-options_{$userId}", now()->addMonth(), function () {
            $years = Invoice::query()
                ->filterByAccess()
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->where('site_id', clientSiteId())
                ->whereNotNull('created_at')
                // ->when(AccountType::isParticipant(), fn($query) => $query->where('invoiceable_id', Auth::user()->id)
                //     ->where('invoiceable_type', User::class)
                //     ->whereHas('invoiceItems', function ($query) {
                //         $query->where('invoice_itemable_type', Participant::class)
                //             ->where('type', InvoiceItemTypeEnum::ParticipantRegistration);
                //     }))
                // ->latest()
                ->pluck('year')
                ->sortDesc();

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => $option
                ];
            })->values();
        });
    }

    /**
     * @param  InvoiceItemTypeEnum|null  $type
     * @return mixed
     */
    public static function getInvoiceItemYearOptions(?InvoiceItemTypeEnum $type = null): mixed
    {
        $userId = AccountType::isParticipant() ? Auth::user()->id : (AccountType::isCharityOwnerOrCharityUser() ? Auth::user()->id : null);

        return Cache::remember("invoice-items-stats-year-filter-options_{$userId}_{$type?->value}", now()->addMonth(), function () use ($type) {
            $years = InvoiceItem::query()
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->when($type, fn($query) => $query->where('type', $type))
                ->whereHas('invoice', function ($query) {
                    $query->where('site_id', clientSiteId());
                })
                ->whereNotNull('created_at')
                // ->orderByDesc('created_at')
                ->pluck('year')
                ->sortDesc();

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => $option
                ];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('invoices-list-order-by-filter-options', now()->addHour(), function () {
            return InvoicesListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getStatusOptions(): mixed
    {
        return Cache::remember('invoices-list-status-filter-options', now()->addHour(), function () {
            return InvoiceStatusEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getHeldOptions(): mixed
    {
        return Cache::remember('invoices-list-held-filter-options', now()->addHour(), function () {
            return BoolYesNoEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getInvoiceItemTypeOptions(): mixed
    {
        return Cache::remember('invoices-list-type-filter-options', now()->addHour(), function () {
            return InvoiceItemTypeEnum::_options();
        });
    }
}
