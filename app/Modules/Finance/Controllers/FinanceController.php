<?php

namespace App\Modules\Finance\Controllers;

use Auth;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\FormatNumber;
use App\Http\Controllers\Controller;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;

use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\Account;
use App\Modules\User\Models\ParticipantProfile;
use App\Modules\Finance\Models\InternalTransaction;

use App\Enums\CurrencyEnum;
use App\Enums\InternalTransactionsListOrderByFieldsEnum;
use App\Enums\ListTypeEnum;
use App\Enums\OrderByDirectionEnum;
use App\Facades\ClientOptions;

use App\Filters\DeletedFilter;
use App\Filters\InternalTransactionsOrderByFilter;

use App\Modules\Finance\Enums\AccountStatusEnum;
use App\Modules\Finance\Enums\AccountTypeEnum;
use App\Modules\Finance\Requests\AccountsHistoryRequest;
use App\Services\DefaultQueryParamService;

/**
 * @group Finance
 * Handles all finance related operations
 * @unauthenticated
 */
class FinanceController extends Controller
{
    use Response,
        SiteTrait,
        UploadTrait,
        DownloadTrait,
        SingularOrPluralTrait;

    /*
    |--------------------------------------------------------------------------
    | Payment Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with payments. That is
    | the creation, view, update, delete and more ...
    |
    */

    public function __construct()
    {
        parent::__construct();

        // $this->middleware('role:can_manage_finances', [
        //     'except' => [

        //     ]
        // ]);  
    }

    /**
     * Get the meta data
     * 
     * @return JsonResponse
     */
    public function metaData(): JsonResponse
    {
        return $this->success('The meta data', 200, [
            'options' => ClientOptions::only('finances', [
                'accounts',
                'account_statuses',
                'account_types',
                'deleted',
                'internal_transactions_order_by',
                'internal_transactions_order_direction'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::InternalTransactions))->setParams([
                'order_by' => InternalTransactionsListOrderByFieldsEnum::CreatedAt->value. ":" . OrderByDirectionEnum::Descending->value,
            ])->getDefaultQueryParams(),
        ]);
    }

    /**
     * Get the balance of the user's wallet
     * 
     * @param  Request       $request
     * @return JsonResponse
     */
    public function balance(Request $request): JsonResponse
    {
        try {
            $wallet = Wallet::whereHasMorph( // TODO: @tsaffi - Improve on this to handle return the balance of different entities like charities, etc
                    'walletable',
                    [ParticipantProfile::class],
                    function ($query) {
                        $query->whereHas('profile', function ($query) {
                            $query->where('user_id', Auth::user()->id);
                        });
                    }
                )->first();
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        $balance =  FormatNumber::formatWithCurrency($wallet?->balance, CurrencyEnum::GBP, true, 2);

        return $this->success('The balance', 200, $balance);
    }

    /**
     * Get the balance of the infinite accounts of the user
     * 
     * @urlParam type string required The entity payment is made for. Must be one of participant_registration, participant_transfer, market_resale, charity_membership,partner_package_assignment,event_places,corporate_credit.  Example: participant_registration
     * @param  Request       $request
     * @return JsonResponse
     */
    public function infiniteBalance(Request $request): JsonResponse
    {
        try {
            $balance = Account::where('type', AccountTypeEnum::Infinite)
                ->whereHas('wallet', function ($query) {
                    $query->whereHasMorph( // TODO: @tsaffi - Improve on this to handle return the balance of different entities like charities, etc
                        'walletable',
                        [ParticipantProfile::class],
                        function ($query) {
                            $query->whereHas('profile', function ($query) {
                                $query->where('user_id', Auth::user()->id);
                            });
                        }
                    );
                })->value('balance');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        $balance =  FormatNumber::formatWithCurrency($balance, CurrencyEnum::GBP, true, 2);

        return $this->success('The infinite balance', 200, $balance);
    }

    /**
     * Get the balance of the finite accounts of the user
     * 
     * @urlParam type string required The entity payment is made for. Must be one of participant_registration, participant_transfer, market_resale, charity_membership,partner_package_assignment,event_places,corporate_credit.  Example: participant_registration
     * @param  Request       $request
     * @return JsonResponse
     */
    public function finiteBalance(Request $request): JsonResponse
    {
        try {
            $balance = Wallet::whereHasMorph( // TODO: @tsaffi - Improve on this to handle return the balance of different entities like charities, etc
                'walletable',
                [ParticipantProfile::class],
                function ($query) {
                    $query->whereHas('profile', function ($query) {
                        $query->where('user_id', Auth::user()->id);
                    });
                }
            )->first()?->finiteAccountsBalance;
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        $balance = is_null($balance) ? 0 : $balance;

        $balance = FormatNumber::formatWithCurrency($balance, CurrencyEnum::GBP, true, 2);

        return $this->success('The finite balance', 200, $balance);
    }

    /**
     * Get the history of the different account types
     * 
     * @queryParam term string Filter by term. The term to search for. No-example
     * @urlParam type string required The entity payment is made for. Must be one of finite, infinte.  Example: finite
     * @queryParam account string Filter by account ref. No-example
     * @queryParam status string Filter by status. Must be one of active, inactive. No-example
     * @queryParam valid_from string Filter by valid_from. Must be a valid date in the format d-m-Y. Example: "22-02-2023"
     * @queryParam valid_to string Filter by valid_to. Must be a valid date in the format d-m-Y. Example: "22-02-2023"
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. These are created_at, valid_from, valid_to. Example: created_at:asc,valid_from:asc,valid_to:desc
     * @param  AccountsHistoryRequest  $request
     * @param  AccountTypeEnum         $type
     * @return JsonResponse
     */
    public function accountsHistory(AccountsHistoryRequest $request, AccountTypeEnum $type): JsonResponse
    {
        try {
            $transactions = InternalTransaction::whereHas('account', function ($query) use ($type, $request) {
                $query->where('type', AccountTypeEnum::from($type->value))
                    ->when($request->filled('account'),
                        function ($query) use ($request) {
                            $query->where('ref', $request->account);
                    })->when($request->filled('status'),
                        function ($query) use ($request) {
                            $query->where('status', AccountStatusEnum::from($request->status));
                        }
                    )->whereHas('wallet', function ($query) {
                        $query->whereHasMorph( // TODO: @tsaffi - Improve on this to handle return the balance of different entities like charities, etc
                            'walletable',
                            [ParticipantProfile::class],
                            function ($query) {
                                $query->whereHas('profile', function ($query) {
                                    $query->where('user_id', Auth::user()->id);
                                });
                            }
                        );
                    });
            })->filterListBy(new InternalTransactionsOrderByFilter)
            ->filterListBy(new DeletedFilter)
            ->latest()
            ->when(
                $request->filled('term'),
                fn ($query) => $query->where(function ($query) use ($request) {
                    $query->where('amount', 'like', "%{$request->term}%")
                        ->orWhere('type', $request->term);
                })
            )->when(
                $request->filled('per_page'),
                fn ($query) => $query->paginate((int) $request->per_page),
                fn ($query) => $query->paginate(10)
            )->withQueryString();
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success("The {$type->value} transaction history", 200, $transactions);
    }
}
