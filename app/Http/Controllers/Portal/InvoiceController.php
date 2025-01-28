<?php

namespace App\Http\Controllers\Portal;

use Auth;
use Illuminate\Support\Facades\DB;
use Log;
use Storage;
use Exception;
use Validator;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use App\Facades\ClientOptions;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Rules\validateInvoiceItemItemRefAgainstItemClass;

use App\Jobs\SendMembershipRenewalInvoiceJob;

use App\Modules\User\Models\User;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResaleRequest;
use App\Modules\Participant\Models\Participant;
use App\Modules\Charity\Models\EventPlaceInvoice;
use App\Modules\Charity\Models\CharityMembership;
use App\Modules\Charity\Models\CharityPartnerPackage;

use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceItemResource;

use App\Http\Requests\InvoiceUpdateRequest;
use App\Http\Requests\InvoiceDeleteRequest;
use App\Http\Requests\InvoiceRestoreRequest;
use App\Http\Requests\InvoiceItemDeleteRequest;
use App\Http\Requests\InvoiceListingQueryParamsRequest;

use App\Http\Helpers\RegexHelper;
use App\Modules\Participant\Resources\ParticipantResource;

use App\Enums\MonthEnum;
use App\Enums\ListTypeEnum;
use App\Enums\BoolYesNoEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\EventPlaceInvoicePeriodInMonthRangeTextEnum;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\InvoiceDataService;
use App\Services\DefaultQueryParamService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;

/**
 * @group Invoices
 * Manages invoices on the application
 * @authenticated
 */
class InvoiceController extends Controller
{
    use Response, SiteTrait, UploadTrait, DownloadTrait, SingularOrPluralTrait;

    /*
    |--------------------------------------------------------------------------
    | Invoice Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with invoices. That is
    | the creation, view, update, delete and more ...
    |
    */

    // TODO: Please take a look at the createInvoice, postCreateInvoice, and deleteInvoice methods of the CharityController
    // on the previous project, import them here while improving on the logic and making sure that each of these methods serve all the different roles.
    // Do well to merge (combine) the methods that are redundant.

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected InvoiceDataService $invoiceDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_invoices', [
            'except' => [
                'index',
                'show',
                'download'
            ]
        ]);
    }

    /**
     * The list of invoices
     *
     * @queryParam type string Filter by type. Must be one of participant_registration, market_resale, charity_membership, partner_package_assignment, event_places, corporate_credit. Example: participant_registration
     * @queryParam status string Filter by status. Must be one of paid, unpaid, refunded. No-example
     * @queryParam held boolean Filter by held. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam price integer[] Filter by a price range. Example: [12, 80]
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: first_name:desc,last_name:asc,full_name:desc
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param InvoiceListingQueryParamsRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(InvoiceListingQueryParamsRequest $request): JsonResponse
    {
        if (! (AccountType::isAdminOrAccountManagerOrDeveloper() || AccountType::isCharityOwnerOrCharityUser())) { // Only these users have access to this resource
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $data = (new CacheDataManager(
            $this->invoiceDataService,
            'getPaginatedList',
            [$request]
        ))->getData();

        $response = [
            ...$data,
            'options' => ClientOptions::only('invoices', [
                'months',
                'deleted',
                'held',
                'types',
                'statuses',
                'years',
                'periods',
                'order_by',
                'deleted',
                'order_direction'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Invoices))->getDefaultQueryParams(),
            'action_messages' => Invoice::$actionMessages
        ];

        return $this->success('The list of invoices', 200, $response);
    }

    /**
     * Edit an invoice
     *
     * @urlParam ref string required The ref of the invoice. Example: 975f9415-1e07-4499-b9eb-54f0ed9e9043
     * @return JsonResponse
     * @throws Exception
     */
    public function edit(string $ref): JsonResponse
    {
        $invoice = (new CacheDataManager(
            $this->invoiceDataService,
            'edit',
            [$ref]
        ))->getData();

        return $this->success('Edit the invoice', 200, [
            'types' => InvoiceItemTypeEnum::_options(),
            'invoice' => new InvoiceResource($invoice),
            'held' => BoolYesNoEnum::_options(),
            'status' => InvoiceStatusEnum::_options([InvoiceStatusEnum::Refunded]),
            'action_messages' => Invoice::$actionMessages
        ]);
    }

    /**
     * Update an invoice
     *
     * @param InvoiceUpdateRequest $request
     * @param string $ref
     * @return JsonResponse
     * @urlParam ref string required The ref of the invoice. Example: 975f9415-1e07-4499-b9eb-54f0ed9e9043
     */
    public function update(InvoiceUpdateRequest $request, string $ref): JsonResponse
    {
        $invoice = Invoice::with(['invoiceable', 'site', 'invoiceItems.invoiceItemable', 'upload']);

        $invoice = $invoice->whereHas('site', function($query) {
            $query->makingRequest();
        })->filterByAccess();

        try {
            if (AccountType::isAdmin()) { // Only the admin can access deleted invoices
                $invoice = $invoice->withTrashed();
            }

            $invoice = $invoice->where('ref', $ref)
                ->firstOrFail();

            try {
                DB::beginTransaction();

                $oldHeld = $invoice->held;

                if (! $invoice->held) { // Ensure the held and send_on properties remain unchanged if the invoice has already been sent

                    $invoice->fill($request->only(['description', 'issue_date', 'due_date']));
                } else {
                    // Set values of held and send_on.
                    $request['held'] = $request->filled('held') ? $request->held : $invoice->held;
                    $request['send_on'] = $request->filled('send_on') ? $request->send_on : $invoice->send_on;

                    if (Carbon::parse($invoice->issue_date)->eq(Carbon::parse($request->issue_date))) { // Update the issue_date when the invoice was sent. NB: Only use logic to set/update the issue_date if the value of the issue_date was not changed/updated by the user.
                        if ($oldHeld && !$request['held']) {
                            $request['issue_date'] = Carbon::now();
                            $request['due_date'] = Carbon::now()->addWeeks(2);
                        } else if ($request->held && $request->filled('send_on')) { // If invoice is still held but send_on was changed
                            $invoice->issue_date = $request->send_on;
                        }
                    }

                    $invoice->fill($request->only(['description', 'issue_date', 'due_date', 'held', 'send_on']));
                }

                $invoice->save();

				if ($oldHeld && !$invoice->held && $invoice->invoiceable_type == Charity::class && $invoice->status == InvoiceStatusEnum::Unpaid && $invoice->invoiceItems()->where('type', InvoiceItemTypeEnum::CharityMembership)->exists()) { // Send the invoice when held is changed from 1 to 0 and the invoice items has the charity_membership type
					$this->dispatch(new SendMembershipRenewalInvoiceJob($invoice->invoiceable, $invoice));
				}

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to update the invoice! Please try again.', 406, $e->getMessage());
            }

        } catch (ModelNotFoundException $e) {

            return $this->error('The invoice was not found!', 404);
        }

        return $this->success('Successfully updated the invoice!', 200, new InvoiceResource($invoice->load(['invoiceable', 'site', 'upload'])));
    }

    /**
     * Delete one or many invoices (Soft delete)
     *
     * @param  InvoiceDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(InvoiceDeleteRequest $request): JsonResponse
    {
        if (! AccountType::isAdmin()) { // Only the administrator can delete an invoice.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $invoices = Invoice::whereHas('site', function($query) {
            $query->hasAccess()
                ->makingRequest();
        });

        try {
            $invoices = $invoices->whereIn('ref', $request->refs)
                ->get();

            if (! $invoices->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($invoices as $invoice) {
                    $invoice->delete();
                }

                DB::commit();

            } catch(QueryException $e) {
                DB::rollback();

                return $this->error("Unable to delete the ". static::singularOrPlural(['invoice', 'invoices'], $request->refs) ."! Please try again.", 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {

            return $this->error("The ". static::singularOrPlural(['invoice was', 'invoices were'], $request->refs) ." not found!", 404);
        }

        return $this->success("Successfully deleted the ". static::singularOrPlural(['invoice', 'invoices'], $request->refs), 200);
    }

    /**
     * Restore one or many invoices
     *
     * @param  InvoiceRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(InvoiceRestoreRequest $request): JsonResponse
    {
        if (! AccountType::isAdmin()) { // Only the administrator can delete an invoice.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $invoices = Invoice::whereHas('site', function ($query) {
            $query->hasAccess()
                ->makingRequest();
        });

        try {
            $invoices = $invoices->whereIn('ref', $request->refs)
                ->onlyTrashed()
                ->get();

            if (! $invoices->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($invoices as $invoice) {
                    $invoice->restore();
                }

                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to restore the '. static::singularOrPlural(['invoice', 'invoices'], $request->refs) .'! Please try again.', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['invoice was', 'invoices were'], $request->refs) .' not found!', 404);
        }

        return $this->success('Successfully restored the '. static::singularOrPlural(['invoice', 'invoices'], $request->refs), 200, new InvoiceResource($invoices));
    }

    /**
     * Delete one or many invoices (Permanently)
     * Only the administrator can delete an invoice permanently.
     *
     * @param  InvoiceDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(InvoiceDeleteRequest $request): JsonResponse
    {
        if (! AccountType::isAdmin()) { // Only the administrator can delete an invoice.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $invoices = Invoice::whereHas('site', function($query) {
            $query->hasAccess()
                ->makingRequest();
        });

        try {
            $invoices = $invoices->whereIn('ref', $request->refs)
                ->withTrashed()
                ->get();

            if (! $invoices->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($invoices as $invoice) {
                    $invoice->invoiceItems()->delete(); // Delete the invoice items

                    $invoice->forceDelete(); // Delete the invoice
                }

                $label = static::singularOrPlural(['invoice', 'invoices'], $request->refs);

                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error($e->getMessage(), 406, $e->getMessage());
            } catch(Exception $e) {
                DB::rollback();
                return $this->error($e->getMessage(), 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error("The ". static::singularOrPlural(['invoice was', 'invoices were'], $request->refs) ." not found!", 404, $e->getMessage());
        }

        return $this->success("Successfully deleted the ". $label ." permanently", 200, new InvoiceResource($invoices));
    }

    /**
     * Download an invoice
     *
     * @urlParam ref string required The ref of the invoice. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function download(string $ref): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        if (! (AccountType::isAdminOrAccountManagerOrDeveloper() || AccountType::isCharityOwnerOrCharityUser())) { // Only these users have access to this resource
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $invoice = Invoice::with('upload');

        $invoice = $invoice->whereHas('site', function($query) {
            $query->makingRequest();
        })->filterByAccess();

        try {
            $invoice = $invoice->where('ref', $ref)
                ->firstOrFail();

            if (Storage::disk(config('filesystems.default'))->exists($invoice->upload->url)) {
                $headers = [
                    'Content-Type' => 'text/pdf',
                ];

                $fileName = $invoice->name . '.pdf';
                $fileName = str_replace(array("/", "\\", ":", "*", "?", "«", "<", ">", "|"), "-", $fileName);

                $path = $invoice->upload->url;

                return static::_download($path, false, $fileName);
            } else {
                return $this->error('The pdf file was not found!', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The invoice was not found!', 406);
        }
    }

    /**
     * Export invoices
     *
     * @queryParam type string Filter by type. Must be one of participant_registration, market_resale, charity_membership, partner_package_assignment, event_places, corporate_credit. Example: participant_registration
     * @queryParam status string Filter by status. Must be one of paid, unpaid. No-example
     * @queryParam held boolean Filter by held. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam price integer[] Filter by a price range. Example: [12, 80]
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: first_name:desc,last_name:asc,full_name:desc
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param InvoiceListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(InvoiceListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->invoiceDataService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting events\' data.', 400);
        }
    }

    /**
     * Generate an invoice (the pdf file)
     *
     * @urlParam ref string required The ref of the invoice. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     * @return JsonResponse
     */
	public function generateInvoicePdf(string $ref): JsonResponse
    {
        $invoice = Invoice::with(['invoiceable', 'site', 'invoiceItems.invoiceItemable', 'upload']);

        $invoice = $invoice->whereHas('site', function($query) {
            $query->makingRequest();
        });

        if (AccountType::isAdmin()) { // Ensure the admins only have access to the invoices of their sites
            $invoice = $invoice->whereHas('site', function($query) {
                $query->hasAccess()
                    ->makingRequest();
            });
        }

        if (AccountType::isAccountManager()) {
            $invoice = $invoice->whereHasMorph(
                'invoiceable',
                [Charity::class],
                function($query) {
                    $query = $query->whereHas('charityManager', function($query) {
                        $query->where('user_id', Auth::user()->id);
                    });
                }
            );
        }

        try {
            $invoice = $invoice->where('ref', $ref)
                ->firstOrFail();

            $invoice = Invoice::generatePdf($invoice);
        } catch (ModelNotFoundException $e) {
            return $this->error('The invoice was not found!', 404);
        }

		return $this->success('Successfully generated the invoice!', 200, $invoice->load(['invoiceable', 'upload']));
	}

    /**
     * Pay for an invoice
     *
     * @param  Request  $request
     * @urlParam ref string required The ref of the invoice. Example: 975f9415-1e07-4499-b9eb-54f0ed9e9043
     * @return JsonResponse
     */
    public function pay(Request $request, string $ref): JsonResponse
    {
        // TODO: Take a look at the logic of the payInvoice() method of the UserController on the sport-for-api repository and write the logic of this method

        return $this->success('Not yet implemented', 200);
    }

    /**
     * Add an invoice item
     *
     * @urlParam ref string required The ref of the invoice. Example: 978017ae-6c3f-4d6e-9b91-e0606d6d3e44
     * @return JsonResponse
     *
     */
    public function createInvoiceItem(string $ref): JsonResponse
    {
        try {
            $invoice = Invoice::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $ref)
            ->firstOrFail();

        } catch (ModelNotFoundException $e) {

            return $this->error('The invoice was not found!', 404);
        }

        return $this->success('Create an invoice item', 200, [
            'types' => InvoiceItemTypeEnum::_options(),
            'invoice' => new InvoiceResource($invoice)
        ]);
    }

    /**
     * Store the invoice item
     *
     * @param Request $request
     * @urlParam ref string required The ref of the invoice. Example: 978017ae-6c3f-4d6e-9b91-e0606d6d3e44
     * @return JsonResponse
     *
     */
    public function storeInvoiceItem(Request $request, string $ref): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The class associated to the invoice. Must be one of App\Modules\Charity\Models\CharityMembership, App\Modules\Participant\Models\Participant, App\Modules\Charity\Models\EventPlaceInvoice. Example: App\Modules\Participant\Models\Participant
            'item_class' => ['required', 'string'],
            // The type. Must be one of 979bcb4e-9556-499b-9d9b-5e3bef80a79d, 979bcb4e-bfac-4ac0-af69-956d04f4289c. Example: 979bcb4e-bfac-4ac0-af69-956d04f4289c
            'item_ref' => ['required', 'string', new validateInvoiceItemItemRefAgainstItemClass],
            // The type. Must be one of participant_registration, charity_membership, maket_places, event_places etc. Example: participant_registration
            'type' => ['required', new Enum(InvoiceItemTypeEnum::class)],
            // The price. Example: 53.92
            'price' => ['required', 'numeric'],
            // The discount. Example: 20.87
            'discount' => ['sometimes', 'required', 'numeric', 'max:100']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $invoice = Invoice::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $ref)
            ->firstOrFail();

            try {
                $item = new InvoiceItem();

                $item->fill($request->all());

                $item->invoice_id = $invoice->id;
                $item->invoice_itemable_type = $request->item_class;
                $item->invoice_itemable_id = $request->item_class::where('ref', $request->item_ref)->value('id');

                $item->save();

            } catch (QueryException $e) {

                return $this->error('Unable to create the invoice item! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The invoice was not found!', 404);
        }

        return $this->success('Successfully created the invoice item!', 201, new InvoiceItemResource($item));
    }

    /**
     * Edit an invoice item
     *
     * @urlParam ref string required The ref of the invoice. Example: 978017ae-6c3f-4d6e-9b91-e0606d6d3e44
     * @urlParam invoiceItemRef string required The ref of the invoice item. Example: 97963856-9aea-473f-987e-9fd84d9403bb
     * @return JsonResponse
     */
    public function editInvoiceItem(string $ref, string $invoiceItemRef): JsonResponse
    {
        try {
            $invoice = Invoice::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $ref)
            ->firstOrFail();

            try {
                $item = $invoice->invoiceItems()->where('ref', $invoiceItemRef)->firstOrFail();
                $item['label'] = $item->loadRelationsThenGetLabel();
            } catch (ModelNotFoundException $e) {
                return $this->error('The invoice item was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The invoice was not found!', 404);
        }

        return $this->success('Edit the invoice item!', 200, [
            'invoice_item' => new InvoiceItemResource($item),
            'types' => InvoiceItemTypeEnum::_options()
        ]);
    }

    /**
     * Update an invoice item
     *
     * @param Request $request
     * @urlParam ref string required The ref of the invoice. Example: 978017ae-6c3f-4d6e-9b91-e0606d6d3e44
     * @urlParam invoiceItemRef string required The ref of the invoice item. Example: 97963856-9aea-473f-987e-9fd84d9403bb
     * @return JsonResponse
     */
    public function updateInvoiceItem(Request $request, string $ref, string $invoiceItemRef): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // // The class associated to the invoice. Must be one of App\Modules\Charity\Models\CharityMembership, App\Modules\Participant\Models\Participant, App\Modules\Charity\Models\EventPlaceInvoice. Example: App\Modules\Participant\Models\Participant
            // 'item_class' => ['sometimes', 'required_with:item_ref', 'string'],
            // // The type. Must be one of 979bcb4e-9556-499b-9d9b-5e3bef80a79d, 979bcb4e-bfac-4ac0-af69-956d04f4289c. Example: 979bcb4e-bfac-4ac0-af69-956d04f4289c
            // 'item_ref' => ['sometimes', 'required_with:item_class', 'string', new validateInvoiceItemItemRefAgainstItemClass],
            // The type. Must be one of participant_registration, charity_membership, maket_places, event_places etc. Example: participant_registration
            // 'type' => ['sometimes', 'required', new Enum(InvoiceItemTypeEnum::class)],
            // The price. Example: 53.92
            'price' => ['sometimes', 'required', 'numeric'],
            // The discount. Example: 20.87
            'discount' => ['sometimes', 'required', 'numeric', 'max:100']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $invoice = Invoice::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $ref)
            ->firstOrFail();

            try {
                $item = $invoice->invoiceItems()->where('ref', $invoiceItemRef)->firstOrFail();

                try {
                    // $request['invoice_itemable_type'] = $request->item_class;
                    // $request['invoice_itemable_id'] = $request->item_class::where('ref', $request->item_ref)->value('id');
                    $item->update($request->only(['price', 'discount'])); // TODO: @tsaffi - Prevent the status of an invoice item from being changed once it is set to transferred (as a second invoice item probably exists with a different status - paid for participant transfer for example)
                } catch (QueryException $e) {
                    return $this->error('Unable to update the invoice item! Please try again.', 406, $e->getMessage());
                }

            } catch (ModelNotFoundException $e) {
                return $this->error('The invoice item was not found!', 404);
            }

        } catch (ModelNotFoundException $e) {
            return $this->error('The invoice was not found!', 404);
        }

        return $this->success('Successfully updated the invoice item!', 200, new InvoiceItemResource($item));
    }

    /**
     * Delete one or many invoice items
     *
     * @param  InvoiceItemDeleteRequest $request
     * @urlParam ref string required The ref of the invoice. Example: 979393e7-6826-409b-952c-56689414d5a7
     * @return JsonResponse
     */
    public function destroyInvoiceItem(InvoiceItemDeleteRequest $request, string $ref): JsonResponse
    {
        if (! AccountType::isAdmin()) { // Only the administrator can delete an invoice item.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            $invoice = Invoice::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $ref)
            ->firstOrFail();

            try {
                $invoiceItems = $invoice->invoiceItems()
                    ->whereIn('ref', $request->refs)
                    ->get();

                if (! $invoiceItems->count()) {
                    throw new ModelNotFoundException();
                }

                try {
                    DB::beginTransaction();

                    foreach ($invoiceItems as $item) {
                        $item->delete();
                    }

                    $redirect = $invoice->invoiceItems()->count() < 1; // Redirect the user to the listings page since the invoice got deleted by the deleted() event (occurs when the only/last invoice item gets deleted)

                    DB::commit();
                } catch(QueryException $e) {
                    DB::rollback();
                    return $this->error('Unable to delete the invoice item(s)! Please try again.', 406);
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The invoice item(s) was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The invoice was not found!', 404);
        }

        return $this->success("Successfully deleted the invoice item(s)!" . ( (isset($redirect) && $redirect) ? " The invoice no longer exists." : "" ), 200, [
            'invoice_items' => $invoiceItems,
            'redirect' => $redirect ?? 0
        ]);
    }

    /**
     * Paginated charity memberships for dropdown fields
     *
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function charityMemberships(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charityMemberships = CharityMembership::with('charity:id,name')
            ->whereHas('charity', function ($query) {
                $query->filterByAccess();
            });

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $charityMemberships = $charityMemberships->paginate($perPage);

        foreach ($charityMemberships as $key => $membership) {
            $charityMemberships[$key]->class = CharityMembership::class;
            $charityMemberships[$key]->label = InvoiceItem::getLabel($charityMemberships[$key]->class, $membership);
        }

        return $this->success('All Charity memberships', 200, [
            'charity_memberships' => $charityMemberships
        ]);
    }

    /**
     * Paginated participants for dropdown fields.
     *
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     */
    public function participants(Request $request): JsonResponse
    {
        $participants = Participant::select('id', 'ref', 'user_id', 'event_event_category_id', 'added_via', 'status')
            ->appendsOnly([
                'latest_action',
                'formatted_status',
                'fee_type',
                'payment_status',
                'custom_name'
            ])
            ->with(['user:id,first_name,last_name,email', 'charity:id,ref,name,slug', 'eventEventCategory.event:id,ref,name,slug', 'eventEventCategory.eventCategory:id,ref,name,slug'])
            ->whereHas('eventEventCategory.eventCategory', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

        $participants = $participants->orderBy(
            User::select('first_name')
                ->whereColumn('user_id', 'users.id')
                ->orderBy('first_name')
                ->limit(1)
        );

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $participants = $participants->paginate($perPage);

        foreach ($participants as $key => $participant) {
            $participants[$key]->class = Participant::class;
            $participants[$key]->label = InvoiceItem::getLabel($participants[$key]->class, $participant);
        }

        return $this->success('All participants', 200, [
            'participants' => new ParticipantResource($participants)
        ]);
    }

    /**
     * Paginated charity partner package for dropdown fields.
     *
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function charityPartnerPackages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charityPartnerPackages = CharityPartnerPackage::with('charity:id,name')
            ->whereHas('charity', function ($query) {
                $query->filterByAccess();
            });

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $charityPartnerPackages = $charityPartnerPackages->paginate($perPage);

        foreach ($charityPartnerPackages as $key => $cpp) {
            $charityPartnerPackages[$key]->class = CharityPartnerPackage::class;
            $charityPartnerPackages[$key]->label = InvoiceItem::getLabel($charityPartnerPackages[$key]->class, $cpp);
        }

        return $this->success('All charity partner packages', 200, [
            'charity_partner_packages' => $charityPartnerPackages
        ]);
    }

    /**
     * Paginated resale requests for dropdown fields.
     *
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function resaleRequests(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $resaleRequests = ResaleRequest::with('charity:id,name')
            ->whereHas('charity', function ($query) {
                $query->filterByAccess();
            });

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $resaleRequests = $resaleRequests->paginate($perPage);

        foreach ($resaleRequests as $key => $request) {
            $resaleRequests[$key]->class = ResaleRequest::class;
            $resaleRequests[$key]->label = InvoiceItem::getLabel($resaleRequests[$key]->class, $request);
        }

        return $this->success('All resale requests', 200, [
            'resale_requests' => $resaleRequests
        ]);
    }

    /**
     * Paginated event place invoices for dropdown fields.
     *
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function eventPlaceInvoices(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $epis = EventPlaceInvoice::with('charity:id,name')
            ->whereHas('charity', function ($query) {
                $query->filterByAccess();
            });

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $epis = $epis->paginate($perPage);

        foreach ($epis as $key => $epi) {
            $epis[$key]->class = EventPlaceInvoice::class;
            $epis[$key]->label = InvoiceItem::getLabel($epis[$key]->class, $epi);
        }

        return $this->success('All event place invoices', 200, [
            'event_place_invoices' => $epis
        ]);
    }

    /**
     * Format the value of the categories field
     *
     * @param  Invoice $invoice
     * @return ?string
     */
    private function getInvoiceItemsValue(Invoice $invoice): ?string
    {
        if ($invoice->invoiceItems) {

            $items = null;

            foreach ($invoice->invoiceItems as $key => $item) {
                $key += 1;
                $type = $item['type']->name;
                $price = $item['formatted_price'];
                $discount = $item['discount'] ?? 'N/A';

                $items .=
                    "$key. [Type]: $type, [Discount]: $discount, [Price]: $price" . PHP_EOL
                ;
            }

            return $items ?? 'N/A';
        }

        return 'N/A';
    }
}
