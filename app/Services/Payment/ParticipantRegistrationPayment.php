<?php

namespace App\Services\Payment;

use App\Modules\Finance\Enums\AccountTypeEnum;
use App\Modules\Finance\Models\Account;
use App\Modules\User\Models\ParticipantProfile;
use Illuminate\Support\Facades\DB;
use Log;
use Str;
use Exception;
use Validator;
use Carbon\Carbon;
use App\Mail\Mail;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Helpers\FormatNumber;
use App\Modules\Event\Exceptions\IsActiveException;
use App\Services\Payment\Contracts\PaymentInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Modules\Event\Exceptions\HasAvailablePlacesException;
use App\Modules\Finance\Requests\ParticipantRegistrationCreateRequest;
use App\Modules\Finance\Requests\ParticipantRegistrationUpdateRequest;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Modules\User\Models\User;
use App\Http\Helpers\AccountType;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Finance\Models\OngoingExternalTransaction;

use App\Events\ParticipantNewRegistrationsEvent;
use App\Jobs\ResendEmailJob;
use App\Mail\participant\entry\ParticipantFailedTransferMail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Modules\Finance\Requests\ParticipantTransferUpdateRequest;
use App\Modules\Participant\Exceptions\IsRegisteredException;
use App\Modules\Setting\Models\SettingCustomField;

use App\Enums\GenderEnum;
use App\Enums\FeeTypeEnum;
use App\Enums\CurrencyEnum;
use App\Enums\InvoiceStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\InvoiceItemStatusEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\SettingCustomFieldKeyEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Modules\Finance\Enums\OngoingExternalTransactionStateEnum;
use App\Modules\Finance\Enums\PaymentTypeEnum;
use App\Modules\Finance\Enums\TransactionTypeEnum;
use App\Modules\Finance\Enums\TransactionStatusEnum;
use App\Modules\Finance\Enums\PaymentMethodsTypesEnum;
use App\Modules\Finance\Enums\TransactionPaymentMethodEnum;
use App\Modules\Finance\Enums\OngoingExternalTransactionStatusEnum;
use App\Modules\Finance\Requests\ParticipantRegistrationPostPaymentPayloadRequest;
use App\Modules\Finance\Requests\ParticipantTransferPostPaymentPayloadRequest;
use App\Traits\Response;
use App\Traits\SiteTrait;

use Stripe\StripeClient;

class ParticipantRegistrationPayment extends Payment implements PaymentInterface
{
    use SiteTrait,
        Response;

    /**
     * Proceed to Checkout
     * 
     * @param  Request  $request
     * @return array
     */
    public function proceedToCheckout(Request $request): array
    {
        $prcr = new ParticipantRegistrationCreateRequest();
        $validator = Validator::make($request->all(), $prcr->rules(), $prcr->messages());

        if ($validator->fails()) { // Validate the request
            return $prcr->failedValidation($validator);
        }

        try {
            $eecs = EventEventCategory::select('id', 'ref', 'event_id', 'event_category_id', 'local_fee', 'international_fee')
                ->whereIn('ref', collect($request->eec)->pluck('ref'))->get(); // TODO: Make this query more efficient by using query builder

            // Check if any item in $request->eec has the 'qty' key
            $hasQty = !empty(array_filter($request->eec, function($item) {
                return array_key_exists('qty', $item);
            }));

            if ($hasQty) {
                // Call amountQty if 'qty' is present in any item
                $qtys = $request->eec;
                $amount = $this->amountQty($eecs, true, $qtys);
            } else {
                $amount = $this->amount($eecs, true);
            }

            if ($amount > 30) { // Stripe Exception - Amount must be at least £0.30 gbp
                $request['amount'] = $amount;
                return [...$this->createPaymentIntent($request), ...$this->metaData()];
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return [
            'payment_required' => false,
            'message' => 'Ready for checkout!',
            ...$this->metaData()
        ];
    }

    /**
     * Create payment intent
     * 
     * @param  Request  $request
     * @return array
     */
    public function createPaymentIntent(Request $request): array
    {
        $prcr = new ParticipantRegistrationCreateRequest();
        $validator = Validator::make($request->all(), $prcr->rules(), $prcr->messages());

        if ($validator->fails()) { // Validate the request
            return $prcr->failedValidation($validator);
        }

        try {
            $data = static::getOrCreateCustomer($request);
            $user = $data['user'] ?? null;
            unset($data['user']);

            if (isset($data['customer']) && $user) { // Payment methods are only saved for existing users
                $savedPaymentMethods = $this->stripe->customers->allPaymentMethods($data['customer']);
                $_savedPaymentMethodsIdentifier = [];
                $savedPaymentMethodsData = [];

                foreach ($savedPaymentMethods->data as $key => $savedPaymentMethod) {
                    $skip = true;

                    if (in_array($savedPaymentMethod->type, ['card', 'paypal', 'bacs_debit'])) {
                        if ($savedPaymentMethod->type == "paypal") {
                            if (!in_array($savedPaymentMethod->paypal->payer_email, $_savedPaymentMethodsIdentifier)) {
                                $_savedPaymentMethodsIdentifier[] = $savedPaymentMethod->paypal->payer_email;
                                $skip = false;
                            }
                        } else {
                            $identifier = $savedPaymentMethod->{$savedPaymentMethod->type}->fingerprint . '-' . $savedPaymentMethod->{$savedPaymentMethod->type}->last4;

                            if (!in_array($identifier, $_savedPaymentMethodsIdentifier)) {
                                $_savedPaymentMethodsIdentifier[] = $identifier;
                                $skip = false;
                            }
                        }
                    }

                    if (!$skip) {
                        $$savedPaymentMethod['name'] = TransactionPaymentMethodEnum::tryFrom($savedPaymentMethod->type)?->name;
                        $savedPaymentMethodsData[] = $savedPaymentMethod;
                    }
                }

                $savedPaymentMethods['data'] = $savedPaymentMethodsData;
            }

            $paymentIntent = $this->stripe->paymentIntents->create([ // Create a PaymentIntent with amount and currency
                'amount' => $request->amount,
                'currency' => 'gbp',
                'description' => 'The payment description',
                'automatic_payment_methods' => [ // In the latest version of the API, specifying the `automatic_payment_methods` parameter is optional because Stripe enables its functionality by default.
                    'enabled' => true,
                    // 'allow_redirects' => 'never'
                ],
                'metadata' => [
                    'X-Client-Key' => $request->header('X-Client-Key'), // Set the X-Client-Key
                    'type' => PaymentTypeEnum::ParticipantRegistration->value
                ],
                // 'customer' => $customer->id,
                // 'setup_future_usage' => 'off_session', // Save card for future on_session/off_session payments.
                // 'mandate_data' => [
                //     'customer_acceptance' => [
                //         'type' => 'offline',
                //         'accepted_at' => Carbon::now()->timestamp
                //     ]
                // ],


                // 'payment_method_types' => ['paypal'],
                // 'confirm' => true,
                // // 'confirmation_method' => 'automatic',
                // 'payment_method_data' => [
                //     'type' => 'paypal',
                // ],
                // 'return_url' => 'https://7dc0-154-72-167-149.ngrok-free.app',
                // 'payment_method' => 'pm_1OOi4mEK2GzEipqWF7xF1mGz',
                ...$data
            ]);

            // $this->stripe->paymentMethods->attach(
            //     $paymentIntent->payment_method,
            //     ['customer' => $customer->id]
            // );

            // $this->stripe->customers->allPaymentMethods($customer->id);

            $ongoingExternalTransaction = OngoingExternalTransaction::create([ // Create the pending payment
                'payment_intent_id' => $paymentIntent->id,
                'status' => OngoingExternalTransactionStatusEnum::Pending,
                'amount' => $request->amount,
                'email' => isset($request->user) && isset($request->user['email']) ? $request->user['email']: null,
                'user_id' => $user?->id,
                'payload' => [
                    'payload' => $request->all()
                ]
            ]);

            Log::channel($this->site->code . 'stripepaymentintent')->info('Payment intent created - ' . json_encode(['id' => $paymentIntent->id, 'client_secret' => $paymentIntent->client_secret]));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $paymentMethods = file_get_contents(base_path('json/paymentMethods.json')); // Read the JSON file
        $paymentMethods = json_decode($paymentMethods,true); // Decode the JSON file

        return [
            'payment_required' => true,
            'client_secret' => $paymentIntent->client_secret,
            'ongoing_external_transaction' => $ongoingExternalTransaction,
            'amount' => FormatNumber::convertAmount($paymentIntent->amount, CurrencyEnum::GBP, true),
            'currency' => $paymentIntent->currency,
            'saved_payment_methods' => $savedPaymentMethods ?? null,
            'payment_methods_types' => PaymentMethodsTypesEnum::_options(!isset($savedPaymentMethods) ? [PaymentMethodsTypesEnum::SavedPaymentMethods] : []),
            'payment_method' => $paymentMethods,
            'message' => 'Successfully created the client key!'
        ];
    }

    /**
     * Checkout | Update payment intent - For events that require payment
     * 
     * @param  Request                     $request
     * @param  OngoingExternalTransaction  $ongoingExternalTransaction
     * @return array
     */
    public function payCheckout(Request $request, OngoingExternalTransaction $ongoingExternalTransaction): array
    {
        $prur = new ParticipantRegistrationUpdateRequest();
        $validator = Validator::make($request->all(), $prur->rules(), $prur->messages());

        if ($validator->fails()) { // Validate the request
            return $prur->failedValidation($validator);
        }

        try {
            if ($request->filled('added_via') && $request->added_via == ParticipantAddedViaEnum::Transfer->value) {
                $amount = FormatNumber::convertAmount($ongoingExternalTransaction->amount, CurrencyEnum::GBP);
            } else {
                $eecs = EventEventCategory::select('id', 'ref', 'event_id', 'event_category_id', 'local_fee', 'international_fee')
                    ->whereIn('ref', collect($request->eec)->pluck('ref'))->get();
                            // Check if any item in $request->eec has the 'qty' key
                $hasQty = !empty(array_filter($request->eec, function($item) {
                    return array_key_exists('qty', $item);
                }));

                if ($hasQty) {
                    // Call amountQty if 'qty' is present in any item
                    $qtys = $request->eec;
                    $amount = $this->amountQty($eecs, true, $qtys);
                } else {
                    $amount = $this->amount($eecs, true);
                }
            }

            if ($amount > 30) { // Stripe Exception - Amount must be at least £0.30 gbp
                $data = static::getOrCreateCustomer($request);
                $user = $data['user'] ?? null;
                unset($data['user']);

                if (isset($data['customer']) && $user) { // Payment methods are only saved for existing users
                    $savedPaymentMethods = $this->stripe->customers->allPaymentMethods($data['customer']);
                    $_savedPaymentMethodsIdentifier = [];
                    $savedPaymentMethodsData = [];
    
                    foreach ($savedPaymentMethods->data as $key => $savedPaymentMethod) {
                        $skip = true;
    
                        if (in_array($savedPaymentMethod->type, ['card', 'paypal', 'bacs_debit'])) {
                            if ($savedPaymentMethod->type == "paypal") {
                                if (!in_array($savedPaymentMethod->paypal->payer_email, $_savedPaymentMethodsIdentifier)) {
                                    $_savedPaymentMethodsIdentifier[] = $savedPaymentMethod->paypal->payer_email;
                                    $skip = false;
                                }
                            } else {
                                $identifier = $savedPaymentMethod->{$savedPaymentMethod->type}->fingerprint . '-' . $savedPaymentMethod->{$savedPaymentMethod->type}->last4;
    
                                if (!in_array($identifier, $_savedPaymentMethodsIdentifier)) {
                                    $_savedPaymentMethodsIdentifier[] = $identifier;
                                    $skip = false;
                                }
                            }
                        }
    
                        if (!$skip) {
                            $$savedPaymentMethod['name'] = TransactionPaymentMethodEnum::tryFrom($savedPaymentMethod->type)?->name;
                            $savedPaymentMethodsData[] = $savedPaymentMethod;
                        }
                    }
    
                    $savedPaymentMethods['data'] = $savedPaymentMethodsData;
                }
    
                if ($request->filled('save_payment_method')) { // Save the payment method
                    $data['setup_future_usage'] = 'off_session'; // Save card for future on_session/off_session payments.

                    // $paymentMethod = $this->stripe->paymentMethods->retrieve($request->paymentMethodId);
                    // $this->stripe->paymentMethods->attach(
                    //     $paymentMethod->id,
                    //     ['customer' => $data['customer']]
                    // );
                }

                if ($request->filled('default_payment_method')) { // Set the default payment method
                    $data['invoice_settings.default_payment_method'] = true;
                }

                $paymentIntent = $this->stripe->paymentIntents->retrieve($ongoingExternalTransaction->payment_intent_id); // Get the payment intent

                if ($paymentIntent->customer) { // Stripe Exception (occurs even when the customer value wasn't changed) - You cannot modify `customer` on a PaymentIntent once it already has been set. To fulfill a payment with a different Customer, cancel this PaymentIntent and create a new one.
                    unset($data['customer']);
                }

                $paymentIntent = $this->stripe->paymentIntents->update($ongoingExternalTransaction->payment_intent_id, [ // Update the payment intent
                    'amount' => $amount,
                    'metadata' => [
                        'X-Client-Key' => $request->header('X-Client-Key'), // Set the X-Client-Key
                        'type' => PaymentTypeEnum::ParticipantRegistration->value
                    ],
                    ...$data
                ]);

                $ongoingExternalTransaction->update([
                    'amount' => $amount,
                    'email' => isset($request->user) && isset($request->user['email']) ? $request->user['email']: null,
                    'user_id' => $user?->id,
                    'payload' => [
                        'payload' => $request->all()
                    ]
                ]);
            } else {
                Log::channel($this->site->code . 'adminanddeveloper')->info("Wrong endpoint! Called payCheckout for a free event.");
                throw new Exception("Wrong endpoint! Called payCheckout for a free event.");
            }

            Log::channel($this->site->code . 'stripepaymentintent')->info('Payment intent updated - ' . json_encode(['id' => $ongoingExternalTransaction->payment_intent_id]));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $paymentMethods = file_get_contents(base_path('json/paymentMethods.json')); // Read the JSON file
        $paymentMethods = json_decode($paymentMethods,true); // Decode the JSON file

        return [
            'payment_required' => true,
            'proceed_to_payment' => true,
            'client_secret' => $paymentIntent->client_secret,
            'ongoing_external_transaction' => $ongoingExternalTransaction,
            'amount' => FormatNumber::convertAmount($paymentIntent->amount, CurrencyEnum::GBP, true),
            'currency' => $paymentIntent->currency,
            // 'payment_intent' => $paymentIntent,
            'saved_payment_methods' => $savedPaymentMethods ?? null,
            'payment_methods_types' => PaymentMethodsTypesEnum::_options(!isset($savedPaymentMethods) ? [PaymentMethodsTypesEnum::SavedPaymentMethods] : []),
            'message' => 'Ready for checkout!'
        ];
    }

    /**
     * Checkout - For free events
     * 
     * @param  Request  $request
     * @return array
     */
    public function freeCheckout(Request $request): array
    {
        $prur = new ParticipantRegistrationUpdateRequest();
        $validator = Validator::make($request->all(), $prur->rules(), $prur->messages());

        if ($validator->fails()) { // Validate the request
            return $prur->failedValidation($validator);
        }

        try {
            $eecs = EventEventCategory::whereIn('ref', collect($request->eec)->pluck('ref'))->get();
            $amount = $this->amount($eecs, true);

            if ($amount == 0) { // Register participant to free events
                $_request = new ParticipantRegistrationUpdateRequest();

                $_request['email'] = $request->user['email'];
                $_request['first_name'] = $request->user['first_name'] ?? "";
                $_request['last_name'] = $request->user['last_name'] ?? "";
                $_request['payment_status'] = ParticipantPaymentStatusEnum::Paid->value;
                // $_request['fee_type'] = FeeTypeEnum::Local->value;
                $_request['phone'] = $request->user['phone'] ?? null;
                $_request['profile'] = [
                    'postcode' => $request->user['postcode'] ?? null,
                    'dob' => $request->user['dob'] ?? null,
                    'gender' => $request->user['gender'] ?? null
                ];

                $data = [ // Data to be used for the invoice. Track successful and failed registrations
                    'eecs' => [],
                    'user' => $_request->all()
                ];

                try {
                    DB::beginTransaction();
                    Log::debug('Ran');

                    $createTheUser = Participant::createTheUser($_request, ParticipantAddedViaEnum::from($request['added_via'])); // Create the user
                    $user = $createTheUser->user;
                
                    DB::commit();
        
                    foreach ($eecs as $eec) {
                        $_data = [
                            'id' => $eec->id,
                            'ref' => $eec->ref,
                            'name' => $eec->event->formattedName,
                            'category' => $eec->eventCategory?->name,
                            'registration_fee' => $eec->userRegistrationFee($user), // Update the registration fee to that for the user
                            'message' => null
                        ];

                        try {
                            DB::beginTransaction();
        
                            $register = Participant::registerForEvent($request, $eec, ParticipantAddedViaEnum::from($request['added_via']), $user);

                            $_data['participant'] = $register->participant;
                            $_data['reg_status'] = true;

                            DB::commit();
                        } catch (HasAvailablePlacesException $e) {
                            DB::rollback();
                            $_data['reg_status'] = false;
                            $_data['message'] = $e->getMessage();
                            Log::channel($this->site->code . 'admin')->info("HasAvailablePlacesException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                            Log::channel($this->site->code . 'admin')->debug($e);
                        } catch (IsActiveException $e) {
                            DB::rollback();
                            $_data['reg_status'] = false;
                            $_data['message'] = $e->getMessage();
                            Log::channel($this->site->code . 'admin')->info("IsActiveException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                            Log::channel($this->site->code . 'admin')->debug($e);
                        } catch (IsRegisteredException $e) {
                            DB::rollback();
                            $_data['reg_status'] = false;
                            $_data['message'] = $e->getMessage();
                            Log::channel($this->site->code . 'admin')->info("IsRegisteredException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                            Log::channel($this->site->code . 'admin')->debug($e);
                        } catch (Exception $e) {
                            DB::rollback();
                            $_data['reg_status'] = false;
                            $_data['message'] = $e->getMessage();
                            Log::channel($this->site->code . 'stripecharge')->info($e->getMessage());
                            Log::channel($this->site->code . 'adminanddeveloper')->info($e->getMessage()); // LOG a message to notify admins on slack so that they can manually fix this issue since it does not neccessitate a refund
                            Log::channel($this->site->code . 'stripecharge')->debug($e);
                            Log::channel($this->site->code . 'adminanddeveloper')->debug($e);
                        }

                        $data['eecs'][] = $_data;
                    }
                } catch (Exception $e) {
                    DB::rollback();
                    Log::channel($this->site->code . 'adminanddeveloper')->info('Rolling back created user - ' . $e->getMessage());
                    Log::channel($this->site->code . 'adminanddeveloper')->debug($e);

                    $message = 'An error occured! Please contact support.';

                    return [
                        'state' => OngoingExternalTransactionStateEnum::Failed,
                        'status' => OngoingExternalTransactionStatusEnum::Failed,
                        'eecs' => [],
                        'message' => $message
                    ];
                }
            } else {
                Log::channel($this->site->code . 'adminanddeveloper')->info("Wrong endpoint! Called freeCheckout for a non-free event.");
                throw new Exception("Wrong endpoint! Called freeCheckout for a non-free event.");
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        try {
            // Create the invoice
            $invoice = new Invoice();
            $invoice->forceFill([
                'ref' => Str::orderedUuid()->toString(),
                'site_id' => $this->site->id,
                'invoiceable_id' => $user->id,
                'invoiceable_type' => User::class,
                'name' => Invoice::getFormattedName(InvoiceItemTypeEnum::ParticipantRegistration),
                'issue_date' => Carbon::now(),
                'due_date' => Carbon::now(),
                'price' => $amount,
                'status' => InvoiceStatusEnum::Paid,
                'state' => InvoiceStateEnum::Processing,
                'send_on' => Carbon::now()
            ]);

            $invoice->saveQuietly();

            foreach ($data['eecs'] as $key => $eec) { // Create invoice items for the registrations
                $invoiceItem = new InvoiceItem();
                $invoiceItem->forceFill([
                    'ref' => Str::orderedUuid(),
                    'invoice_id' => $invoice->id,
                    'invoice_itemable_id' => $eec['reg_status'] ? $eec['participant']['id'] :  $eec['id'],
                    'invoice_itemable_type' => $eec['reg_status'] ? Participant::class : EventEventCategory::class, // Link the eec to the invoice item for failed registrations and to participant for successful registrations
                    'type' => InvoiceItemTypeEnum::ParticipantRegistration,
                    'status' => InvoiceItemStatusEnum::Paid,
                    'price' => $eec['registration_fee'] ?? 0
                ]);

                $invoiceItem->saveQuietly();

                $data['eecs'][$key]['invoice_item'] = $invoiceItem->ref;
            }

            $invoice->load('invoiceItems');

            // Update the invoice
            Invoice::updatePoNumberField($invoice);
            Invoice::updatePriceField($invoice);

            $failed = $data;
            $passed = $data;

            $passed['eecs'] = collect($data['eecs'])->filter(function ($eec) { // Get eecs that passed registration
                return $eec['reg_status'];
            })->values()->all();

            $failed['eecs'] = collect($data['eecs'])->filter(function ($eec) { // Get eecs that failed to register
                return !$eec['reg_status'];
            })->values()->all();

            if (!empty($failed['eecs'])) {
                $status = $eecs->count() == count($failed['eecs']);
                $failed['message'] = "Registration Successful! You could not be registered to some events and have been refunded for those. Please check your emails for more details.";
                $failed['reason'] = 'places exhausted';
                $failed['wasRecentlyCreated'] = $createTheUser?->wasRecentlyCreated;

                Log::channel($this->site->code . 'adminanddeveloper')->info($status ? "Free event registration process failed!" : "Free event registration process partially completed!");
                $message = $status ? "Registration Failed! Please check your emails for more details." : $failed['message'];
            } else {
                $invoice->update([ // Update the invoice state & status
                    'status' => InvoiceStatusEnum::Paid,
                    'state' =>  InvoiceStateEnum::Complete
                ]);

                Log::channel($this->site->code . 'adminanddeveloper')->info("Payload process fully completed!");
                $message = "Registration Successful!";
            }
        } catch (Exception $e) {
            $message = 'An error occurred. Unable to complete registration. Please contact support!';

            Log::channel($this->site->code . 'adminanddeveloper')->info($message . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->debug($e);
        }

        $extraData = [
            'passed' => $passed ?? [],
            'failed' => $failed ?? [],
            'wasRecentlyCreated' => $createTheUser?->wasRecentlyCreated
        ];

        try {
            event(new ParticipantNewRegistrationsEvent($user, $extraData, $invoice->refresh(), clientSite())); // Notify participant via email
        } catch (Exception $e) { // Issues at the level of the email are less dangerous as the process should have completed
            Log::channel($this->site->code . 'stripecharge')->info("Participant Registration Exception. Unable to process participant registration mail. " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Registration Exception. Unable to process participant registration mail. " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info($e);
        }

        return [
            'state' => OngoingExternalTransactionStateEnum::Completed,
            'status' => isset($status) ? OngoingExternalTransactionStatusEnum::Failed : OngoingExternalTransactionStatusEnum::Successful,
            'eecs' => $data['eecs'],
            'message' => $message
        ];
    }

    /**
     * Checkout - Confirm Payment
     * 
     * @param  string  $id The id of the payment intent.
     * @return object
     */
    public function confirm(string $id): object
    {
        return $this->stripe->paymentIntents->confirm(
            $id,
            [
              'payment_method' => 'pm_card_visa',
              'return_url' => 'https://www.example.com',
            ]
        );
    }

    /**
     * Handle payment method webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentMethod(mixed $request): object
    {
        $result = parent::processPaymentMethod($request);

        $paymentMethod = $result->event->data->object;

        switch ($result->event->type) { // Handle the event
            case 'payment_method.attached':
                Log::channel($this->site->code . 'stripepaymentmethod')->info('The payment method has been attached!');
                break;
            case 'payment_method.automatically_updated':
                Log::channel($this->site->code . 'stripepaymentmethod')->info('The payment method has been automatically updated!');
                break;
            case 'payment_method.card_automatically_updated':
                Log::channel($this->site->code . 'stripepaymentmethod')->info('The payment method card has been automatically_updated!');
                break;
            case 'payment_method.detached':
                Log::channel($this->site->code . 'stripepaymentmethod')->info('The payment method has been detached!');
                break;
            case 'payment_method.updated':
                Log::channel($this->site->code . 'stripepaymentmethod')->info('The payment method was updated!');
                break;
            default:
                Log::channel($this->site->code . 'stripepaymentmethod')->info("Received unknown event type: " . $result->event->type);
                throw new Exception("Unknown payment method event type!");
        }

        return $paymentMethod;
    }

    /**
     * Handle payment link webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentLink(mixed $request): object
    {
        $paymentLink = parent::processPaymentLink($request);

        return $paymentLink;
    }

    /**
     * Handle charge webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processCharge(mixed $request): object
    {
        // if (isset($request->job)) { // COMMENTED OUT DUE TO INABILITY TO PERFROM REFUND FROM PAYMENT INTENT OBJECT AS IT DOESN'T HAVE CHARGE DATA (ID)
            // try {
            //     $ongoingExternalTransaction = OngoingExternalTransaction::where('payment_intent_id', $request->id)
            //         ->firstOrFail();

            //     if ($request->status == "succeeded") {
            //         if ($ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Pending) {
            //             $ongoingExternalTransaction->update([
            //                 'status' => OngoingExternalTransactionStatusEnum::Processing
            //             ]);
        
            //             $charge['amount'] = $request->amount;
            //             $ongoingExternalTransaction = $this->chargeSucceeded($ongoingExternalTransaction, (object) $charge);
            //             $result = $this->processPayload($ongoingExternalTransaction, (object) $charge);
            //         } else { // The post payment has already been processed
            //             Log::channel($this->site->code . 'stripecharge')->info("Payment Intent: {$ongoingExternalTransaction->id} Status: {$request->status} - The post payment has already been processed!");
            //         }
            //     }
            // } catch (\Exception) {
            //     Log::channel($this->site->code . 'stripecharge')->info("The Payment Intent {$request->id} was not found!");
            // }


            // Log::channel('test')->debug("Job running");
            // Log::channel('test')->debug(OngoingExternalTransaction::where('payment_intent_id', $request->id)
            //     ->value('status')?->value);

            // $result = (object) [];
        // } else {
            // Log::channel('test')->debug("Charge running");
            $result = parent::processCharge($request);
            $charge = $result->event->data->object;

            // if ($result->ongoing_external_transaction->status == OngoingExternalTransactionStatusEnum::Pending) {
                $result->ongoing_external_transaction->update([
                    'status' => OngoingExternalTransactionStatusEnum::Processing
                ]);

                switch ($result->event->type) { // Handle the event
                    case 'charge.captured':
                        Log::channel($this->site->code . 'stripecharge')->info('The charge has been captured!');
                        break;
                    case 'charge.expired':
                        Log::channel($this->site->code . 'stripecharge')->info('The charge has expired!');
                        break;
                    case 'charge.pending':
                        Log::channel($this->site->code . 'stripecharge')->info('The charge is pending!');
                        break;
                    case 'charge.failed':
                        $this->chargeFailed($result->ongoing_external_transaction, $charge);
                        break;
                    case 'charge.refunded':
                        Log::channel($this->site->code . 'stripecharge')->info('The refund ' . $result->ongoing_external_transaction->payment_intent_id . ' for ' . FormatNumber::convertAmount($charge->amount_refunded, CurrencyEnum::GBP, true) . ' from a ' . FormatNumber::convertAmount($charge->amount, CurrencyEnum::GBP, true) . ' payment has been updated');

                        Transaction::whereHas('externalTransaction', function ($query) use ($charge) { // Update the payment method
                            $query->where('payment_intent_id', $charge->payment_intent);
                        })->where('type', TransactionTypeEnum::Refund)
                            ->update([ // Update the payment method of the refund transaction
                                'payment_method' => TransactionPaymentMethodEnum::tryFrom($charge->payment_method_details->type)
                            ]);
                        break;
                    case 'charge.updated':
                        Log::channel($this->site->code . 'stripecharge')->info('The charge was updated!');
                        break;
                    case 'charge.succeeded':
                        $ongoingExternalTransaction = $this->chargeSucceeded($result->ongoing_external_transaction, $charge);
                        $result = $this->processPayload($ongoingExternalTransaction, $charge);
                        break;
                    default:
                        Log::channel($this->site->code . 'stripecharge')->info("Received unknown event type: " . $result->event->type);
                        throw new Exception("Unknown charge event type!");
                }
            // } else { // The post payment has already been processed
                // Log::channel($this->site->code . 'stripecharge')->info("Payment Intent: {$result->ongoing_external_transaction->payment_intent_id} Status: {$result->ongoing_external_transaction->status?->name} - The post payment has already been processed!");
            // }
        // }

        return $result;
    }

    /**
     * Validate Payload
     * 
     * @param  array  $payload
     * @return void
     */
    public function validatePayload(array $payload): void
    {
        if ($payload['added_via'] == ParticipantAddedViaEnum::Transfer->value) { // Process participant transfer
            $ptpppr = new ParticipantTransferPostPaymentPayloadRequest();
            $validator = Validator::make($payload, $ptpppr->rules(), $ptpppr->messages());
        } else {
            $prpppr = new ParticipantRegistrationPostPaymentPayloadRequest();
            $validator = Validator::make($payload, $prpppr->rules(), $prpppr->messages());
        }

        if ($validator->fails()) { // No refund is expected here. The admin is notified so they can perform the neccessary action which is to investige, sort the issue then register the participant or perform a refund.
            Log::channel($this->site->code . 'adminanddeveloper')->info('Invalid payload: ' . json_encode($validator->errors()));
            Log::channel($this->site->code . 'adminanddeveloper')->info(json_encode('payload: ' . json_encode($payload)));
            Log::channel($this->site->code . 'stripecharge')->info('Invalid payload: ' . json_encode($validator->errors()));
            throw new Exception('Invalid payload!');
        }
    }

    /**
     * Process Payload - Offer places to the participant
     * 
     * @param  OngoingExternalTransaction  $ongoingExternalTransaction
     * @param  object                      $charge
     * @return object
     */
    public function processPayload(OngoingExternalTransaction $ongoingExternalTransaction, object $charge): object
    {
        $payload = $ongoingExternalTransaction->payload['payload'];

        $this->validatePayload($payload); // Validate the payment to ensure it's correctness (consider softdeleted events)

        $eecs = EventEventCategory::with(['event' => function ($query) {
            $query->withTrashed()
                ->withDrafted();
        }, 'eventCategory' => function ($query) {
            $query->withTrashed();
        }])->whereIn('ref', collect($payload['eec'])->pluck('ref'))
            ->get();

        $request = new ParticipantRegistrationUpdateRequest();

        $request['email'] = $payload['user']['email'];
        $request['first_name'] = $payload['user']['first_name'] ?? null;
        $request['last_name'] = $payload['user']['last_name'] ?? null;
        $request['phone'] = $payload['user']['phone'] ?? null;
        $request['profile'] = [
            'postcode' => $payload['user']['postcode'] ?? null,
            'dob' => $payload['user']['dob'] ?? null,
            'gender' => $payload['user']['gender'] ?? null
        ];
        $request['payment_status'] = ParticipantPaymentStatusEnum::Paid->value;
        // $request['fee_type'] = FeeTypeEnum::Local->value;
        $request['charge_id'] = $charge->id;
        $request['customer'] = $charge->customer ?? null;

        $data = [ // Data to be used for the invoice. Track successful and failed registrations
            'eecs' => [],
            'user' => $request->all()
        ];

        if ($payload['added_via'] == ParticipantAddedViaEnum::Transfer->value) { // Process participant transfer
            if ($request->filled('first_name') || $request->filled('last_name')) {
                if ($user = User::where('email', $request['email'])->first()) {
                    if ($user->first_name != $request['first_name'] || $user->last_name != $request['last_name']) {
                        $user->update($request->only(['first_name', 'last_name']));
                    }

                    if ($request->filled('profile')) { // Update the user's profile
                        Participant::createOrUpdateUserProfile($request, $user);
                    }
                }
            }

            return (new ParticipantTransferPayment($this->stripe, $this->site))->processParticipantTransfer($ongoingExternalTransaction, $charge, $eecs[0], $payload);
        }

        try {
            DB::beginTransaction();

            $createTheUser = Participant::createTheUser($request, ParticipantAddedViaEnum::from($payload['added_via'])); // Create the user
            $user = $createTheUser->user;

            DB::commit();

            foreach ($eecs as $eec) {
                $_data = [
                    'id' => $eec->id,
                    'ref' => $eec->ref,
                    'name' => $eec->event->formattedName,
                    'category' => $eec->eventCategory?->name,
                    'registration_fee' => $eec->userRegistrationFee($user), // Update the registration fee to that for the user
                    'message' => null
                ];

                try {
                    DB::beginTransaction();

                    $register = Participant::registerForEvent($request, $eec, ParticipantAddedViaEnum::from($payload['added_via']), $user);

                    $_data['participant'] = [
                        'id' => $register->participant->id,
                        'charity_id' => $register->participant->charity_id
                    ];
                    $_data['reg_status'] = true;

                    DB::commit();
                } catch (HasAvailablePlacesException $e) {
                    DB::rollback();
                    $_data['reg_status'] = false;
                    $_data['message'] = $e->getMessage();
                    Log::channel($this->site->code . 'stripecharge')->info("HasAvailablePlacesException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                    Log::channel($this->site->code . 'admin')->info("HasAvailablePlacesException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                } catch (IsActiveException $e) {
                    DB::rollback();
                    $_data['reg_status'] = false;
                    $_data['message'] = $e->getMessage();
                    Log::channel($this->site->code . 'stripecharge')->info("IsActiveException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                    Log::channel($this->site->code . 'admin')->info("IsActiveException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                } catch (IsRegisteredException $e) {
                    DB::rollback();
                    $_data['reg_status'] = false;
                    $_data['message'] = $e->getMessage();
                    Log::channel($this->site->code . 'stripecharge')->info("IsRegisteredException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                    Log::channel($this->site->code . 'admin')->info("IsRegisteredException: " . $e->getMessage() . ' - ' . json_encode(["eec_id" => $eec->id, 'name' => $eec->event->name, 'category' => $eec->eventCategory?->name]));
                } catch (Exception $e) {
                    DB::rollback();
                    $_data['reg_status'] = false;
                    $_data['message'] = $e->getMessage();
                    Log::channel($this->site->code . 'stripecharge')->info($e->getMessage());
                    Log::channel($this->site->code . 'adminanddeveloper')->info($e->getMessage());
                }

                $data['eecs'][] = $_data;
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::channel($this->site->code . 'stripecharge')->info('Rolling back created user - ' . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info('Rolling back created user - ' . $e->getMessage());
            Log::channel($this->site->code . 'stripecharge')->debug($e);
            Log::channel($this->site->code . 'adminanddeveloper')->debug($e);

            $message = 'An error occured! Please contact support.';

            $ongoingExternalTransaction->update([
                'state' => OngoingExternalTransactionStateEnum::Failed,
                'status' => OngoingExternalTransactionStatusEnum::Failed,
                'response' => [
                    'state' => OngoingExternalTransactionStateEnum::Failed,
                    'status' => OngoingExternalTransactionStatusEnum::Failed,
                    'message' => $message
                ]
            ]);

            $failed = [];
            $failed['eecs'] = collect($eecs)->map(function ($eec) use ($e) { // Structure the eecs
                return [
                    'id' => $eec->id,
                    'ref' => $eec->ref,
                    'name' => $eec->event->formattedName,
                    'category' => $eec->eventCategory?->name,
                    'registration_fee' => $eec->localRegistrationFee, // Update the registration fee to that for the user (The local registration fee is used since we can't know the user's fee type)
                    'message' => in_array($e->getMessage(), ['The user was soft deleted!', 'The user\'s access was restricted!'])
                        ? 'Your account is currently restricted. Please contact the admin to have this resolved.'
                        : 'An error occured! Please contact support.',
                    'reg_status' => false
                ];
            })->values()->all();

            $transaction = $ongoingExternalTransaction->transactions()->create([ // Create a payment transaction.
                'transactionable_id' => null, // No model is appropriate to be associated to this transaction
                'transactionable_type' => null,
                'user_id' => $ongoingExternalTransaction->user_id,
                'status' => TransactionStatusEnum::Processing,
                'type' => TransactionTypeEnum::Payment,
                'description' => "Payment for the following events: " . collect($failed['eecs'])->pluck('name')->join(', '),
                'amount' => FormatNumber::convertAmount($charge->amount, CurrencyEnum::GBP),
                'payment_method' => $charge->payment_method_details->type
            ]);

            $externalTransaction = $transaction->externalTransaction()->create([ // Create an external transaction for the payment
                'payment_intent_id' => $ongoingExternalTransaction->payment_intent_id,
                'charge_id' => $charge->id,
                'payload' => $ongoingExternalTransaction->payload
            ]);

            $failed['type'] = "full"; // Make a full refund
            $failed['added_via'] = $payload['added_via'];
            $failed['amount'] = $charge->amount; // Get the amount to be refunded. It would have been proper to refund the sum of the registration fees [$this->amount(collect($failed['eecs']), true)]. But since we can know the cause of the exception coming from the user create, we can't know whether the user exists and is restricted from the given site or softdeleted.
                                                // Without this, we can't also know what fee type (local or international) was paid by the user during checkout. Consequently we refund the charge amount as it is the most accurate amount to refund though it prevents us from cutting the PARTICIPANT_REGISTRATION_CHARGE_RATE (set in .env)
            $failed['description'] = "Refund for the following events: " . collect($failed['eecs'])->pluck('name')->join(', ');
            $failed['transactionable_id'] = null; // No model is appropriate to be associated to this transaction
            $failed['transactionable_type'] = null;
            $failed['message'] = "Registration Failed! You could not be registered to the events and have been refunded. Please check your emails for more details.";
            $failed['reason'] = 'An exception occured during the registration process';
            $failed['wasRecentlyCreated'] = false;
            $failed['user'] = $request->all();
            $failed['user_exception'] = true;

            $__data = $passed = [];
            $__data['eecs'] = $failed['eecs'];
            $passed['eecs'] = [];

            $transaction->update(['status' => TransactionStatusEnum::Completed]);

            $this->processPostPaymentRefund($ongoingExternalTransaction, $__data, $failed, $passed, $charge, $failed['reason'], "full");

            return (object) [
                'status' => false,
                'message' => $message
            ];
        }

        try {
            // Create the invoice
            $invoice = new Invoice();
            $invoice->forceFill([
                'ref' => Str::orderedUuid()->toString(),
                'site_id' => $this->site->id,
                'invoiceable_id' => $user->id,
                'invoiceable_type' => User::class,
                'name' => Invoice::getFormattedName(InvoiceItemTypeEnum::ParticipantRegistration),
                'issue_date' => Carbon::now(),
                'due_date' => Carbon::now(),
                'price' => FormatNumber::convertAmount($charge->amount, CurrencyEnum::GBP),
                'status' => InvoiceStatusEnum::Paid,
                'state' => InvoiceStateEnum::Processing,
                'send_on' => Carbon::now()
            ]);

            $invoice->saveQuietly();

            $transaction = $ongoingExternalTransaction->transactions()->create([ // Create a payment transaction for the invoice
                'transactionable_id' => $invoice->id,
                'transactionable_type' => Invoice::class,
                'user_id' => $user->id,
                'status' => TransactionStatusEnum::Processing,
                'type' => TransactionTypeEnum::Payment,
                'description' => "Payment for the following events: " . collect($data['eecs'])->pluck('name')->join(', '),
                'amount' => FormatNumber::convertAmount($charge->amount, CurrencyEnum::GBP),
                'payment_method' => $charge->payment_method_details->type
            ]);

            $externalTransaction = $transaction->externalTransaction()->create([ // Create an external transaction for the payment
                'payment_intent_id' => $ongoingExternalTransaction->payment_intent_id,
                'charge_id' => $charge->id,
                'payload' => $ongoingExternalTransaction->payload
            ]);

            foreach ($data['eecs'] as $key => $eec) { // Create invoice items for the registrations
                $invoiceItem = new InvoiceItem();
                $invoiceItem->forceFill([
                    'ref' => Str::orderedUuid(),
                    'invoice_id' => $invoice->id,
                    'invoice_itemable_id' => $eec['reg_status'] ? $eec['participant']['id'] :  $eec['id'],
                    'invoice_itemable_type' => $eec['reg_status'] ? Participant::class : EventEventCategory::class, // Link the eec to the invoice item for failed registrations and to participant for successful registrations
                    'type' => InvoiceItemTypeEnum::ParticipantRegistration,
                    'status' => InvoiceItemStatusEnum::Paid,
                    'price' => $eec['registration_fee'] ?? 0
                ]);

                $invoiceItem->saveQuietly();

                $data['eecs'][$key]['invoice_item'] = $invoiceItem->ref;
            }

            $invoice->load('invoiceItems');

            // Update the invoice
            Invoice::updatePoNumberField($invoice);
            Invoice::updatePriceField($invoice);

            $failed = $data;
            $passed = $data;

            $passed['eecs'] = collect($data['eecs'])->filter(function ($eec) { // Get eecs that passed registration
                return $eec['reg_status'];
            })->values()->all();

            unset($passed['user']); // Not needed

            $failed['eecs'] = collect($data['eecs'])->filter(function ($eec) { // Get eecs that failed to register
                return !$eec['reg_status'];
            })->values()->all();

            $toRefund['eecs'] = collect($data['eecs'])->filter(function ($eec) { // Get eecs that failed to register and have non free events
                return !$eec['reg_status'] && $eec['registration_fee'];
            })->values()->all();

            if (!empty($failed['eecs']) && !empty($toRefund['eecs'])) {
                $status = $eecs->count() == count($failed['eecs']);

                $failed['type'] = $status ? "full" : "partial"; // Whether the refund made is for some or all the eccs
                $failed['added_via'] = $payload['added_via'];

                $failed['amount'] = $this->amount(collect($failed['eecs']), true); // Get the amount to be refunded
                $failed['description'] = "Refund for the following events: " . collect($failed['eecs'])->pluck('name')->join(', ');
                $failed['transactionable_id'] = $invoice->id; // The id of the invoice associated with the payment
                $failed['transactionable_type'] = Invoice::class;
                $failed['message'] = "Registration Successful! You could not be registered to some events and have been refunded for those. Please check your emails for more details.";
                $failed['reason'] = 'An exception occured during the registration process';
                $failed['wasRecentlyCreated'] = $createTheUser?->wasRecentlyCreated;

                $__data = [];
                $__data['eecs'] = $data['eecs'];

                $transaction->update(['status' => TransactionStatusEnum::Completed]);

                // TODO: "places exhausted" is not the right reason for the refund. Create a method that sets the right reason for this case.
                $this->processPostPaymentRefund($ongoingExternalTransaction, $__data, $failed, $passed, $charge, $failed['reason'], $status ? "full" : "partial");

                Log::channel($this->site->code . 'stripecharge')->info($status ? "Payload process failed!" : "Payload process partially completed!");
                $message = $status ? "Registration Failed! Please check your emails for more details." : $failed['message'];
            } else {
                $transaction->transactionable()->update([ // Update the invoice state & status
                    'status' => InvoiceStatusEnum::Paid,
                    'state' =>  InvoiceStateEnum::Complete
                ]);

                $transaction->update(['status' => TransactionStatusEnum::Completed]);

                Log::channel($this->site->code . 'stripecharge')->info("Payload process fully completed!");
                $message = "Registration Successful!";

                $ongoingExternalTransaction->update([
                    'state' => OngoingExternalTransactionStateEnum::Completed,
                    'status' => OngoingExternalTransactionStatusEnum::Successful,
                    'response' => [
                        'state' => OngoingExternalTransactionStateEnum::Completed,
                        'status' => OngoingExternalTransactionStatusEnum::Successful,
                        'message' => $message,
                        'eecs' => $data['eecs']
                    ]
                ]);
            }
        } catch (Exception $e) {
            $message = 'An error occured! Please contact support.';

            Log::channel($this->site->code . 'stripecharge')->info("Stripe Participant Registration Exception - An error occured! Please contact support: " . $e->getMessage());
            Log::channel($this->site->code . 'stripecharge')->debug($e);

            $ongoingExternalTransaction->update([
                'state' => OngoingExternalTransactionStateEnum::Failed,
                'status' => OngoingExternalTransactionStatusEnum::Failed,
                'response' => [
                    'state' => OngoingExternalTransactionStateEnum::Failed,
                    'status' => OngoingExternalTransactionStatusEnum::Failed,
                    'message' => $message,
                    'eecs' => $data['eecs']
                ]
            ]);
        }

        $extraData = [
            'passed' => $passed ?? [],
            'failed' => $failed ?? [],
            'wasRecentlyCreated' => $createTheUser?->wasRecentlyCreated
        ];

        if (isset($toRefund) && isset($toRefund['eecs']) && empty($toRefund['eecs'])) { // Only send the email here when processing was successful and no refund was made
            try {
                event(new ParticipantNewRegistrationsEvent($user, $extraData, $invoice->refresh(), clientSite())); // Notify participant via email
            } catch (Exception $e) { // Issues at the level of the email are less dangerous as the process should have completed
                Log::channel($this->site->code . 'stripecharge')->info("Participant Registration Exception. Unable to process participant registration mail. " . $e->getMessage());
                Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Registration Exception. Unable to process participant registration mail. " . $e->getMessage());
                Log::channel($this->site->code . 'adminanddeveloper')->info($e);
            }
        }

        return (object) [
            'status' => isset($status) ? false : true,
            'message' => $message
        ];
    }

    /**
     * Process Post Payment Refund - Refund the participant after registration failure
     * 
     * @param  OngoingExternalTransaction   $ongoingExternalTransaction
     * @param  array                        $data
     * @param  array                        $refund
     * @param  array                        $passed
     * @param  object                       $charge
     * @param  string                       $reason
     * @param  string                       $status // Whether the refund made is for some or all the eccs
     * @return void
     */
    public function processPostPaymentRefund(OngoingExternalTransaction $ongoingExternalTransaction, array $data, array $refund, array $passed, object $charge, string $reason, $status = "full"): void
    {
        try { // Process Refund
            $_data = [];
            $user = $refund['user'];
            unset($refund['user']);

            if ($refund['added_via'] != ParticipantAddedViaEnum::Transfer->value) {
                $_data['passed'] = $passed;
                unset($passed['user']);
            }

            if (isset($refund['wasRecentlyCreated'])) {
                $_data['wasRecentlyCreated'] = $refund['wasRecentlyCreated'];
                unset($refund['wasRecentlyCreated']);
            }

            $_refund = $this->stripe->refunds->create([
                'charge' => $charge->id,
                'amount' => $refund['amount'],
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'reason' => $reason,
                    'status' => $status,
                    'amount' => $refund['amount'],
                    'charge_id' => $charge->id,
                    'user' => json_encode($user),
                    // 'eecs' => json_encode($refund['eecs']),
                    'X-Client-Key' => request()->header('X-Client-Key'), // Set the X-Client-Key
                    'type' => PaymentTypeEnum::ParticipantRegistration->value
                ]
            ]);

            $transaction = Transaction::create([ // Create the refund transaction
                'transactionable_id' => $refund['transactionable_id'],
                'transactionable_type' => $refund['transactionable_type'],
                'user_id' => $ongoingExternalTransaction->user_id,
                'ongoing_external_transaction_id' => $ongoingExternalTransaction->id,
                'status' => TransactionStatusEnum::Pending,
                'type' => TransactionTypeEnum::Refund,
                'amount' => FormatNumber::convertAmount($refund['amount'], CurrencyEnum::GBP),
                'description' => $refund['description'] ?? null
            ]);

            $transaction->externalTransaction()->create([ // Create an external transaction for the refund
                'payment_intent_id' => $ongoingExternalTransaction->payment_intent_id,
                'charge_id' => $charge->id,
                'refund_id' => $_refund->id,
                'payload' => [
                    'user' => $user,
                    'refund' => $refund,
                    'refund_id' => $_refund->id,
                    ...$data,
                    ...$_data
                ]
            ]);

            $this->postPaymentRefundSuccess($_refund, $transaction);

            Log::channel($this->site->code . 'stripecharge')->info("Refund Successful!");
        } catch (Exception $e) {
            Log::channel($this->site->code . 'stripecharge')->info("Refund Exception: " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Refund Exception: " . $e->getMessage());
            Log::channel($this->site->code . 'stripecharge')->debug($e);
            Log::channel($this->site->code . 'adminanddeveloper')->debug($e);
            // TODO: @tsaffi - Notifify developers & admin on slack
        }
    }

    /**
     * Post Payment Refund Success- Create invoice after refund is successful
     * 
     * @param  object       $data
     * @param  Transaction  $transaction
     * @return void
     */
    public function postPaymentRefundSuccess(object $data, $transaction): void
    {
        $metadata = (object) $data->metadata;
        $metadata->user = json_decode($data->metadata['user']);
        // $metadata->eecs = json_decode($data->metadata['eecs']);

        try { // Save tracking information
            DB::beginTransaction();

            if (isset($transaction->externalTransaction->payload['refund']) && isset($transaction->externalTransaction->payload['refund']['user_exception'])) {
                $email = $metadata->user->email;
            } else {
                $user = User::updateOrCreate([
                    'email' => $metadata->user->email,
                ], [
                    'first_name' => $metadata->user->first_name,
                    'last_name' => $metadata->user->last_name
                ]);

                $user->bootstrapUserRelatedProperties(); // Assign the participant role and associated permissions if the user doesn't have them.
            }

            if ($transaction->externalTransaction->payload['refund']['added_via'] != ParticipantAddedViaEnum::Transfer->value) {
                if (isset($transaction->externalTransaction->payload['refund']['eecs']) && isset($transaction->externalTransaction->payload['refund']['eecs'][0]) && isset($transaction->externalTransaction->payload['refund']['eecs'][0]['invoice_item'])) {
                    foreach ($transaction->externalTransaction->payload['refund']['eecs'] as $eec) {
                        InvoiceItem::where('ref', $eec['invoice_item'])->update([
                            'status' => InvoiceItemStatusEnum::Refunded
                        ]);
                    }

                    $condition = isset($transaction->externalTransaction->payload['refund']) && $transaction->externalTransaction->payload['refund']['type'] == 'full';
                    $transaction->transactionable()->update([ // Update the invoice state & status
                        'status' => $condition ? InvoiceStatusEnum::Refunded : InvoiceStatusEnum::Paid,
                        'state' => $condition ? InvoiceStateEnum::Complete : InvoiceStateEnum::Partial
                    ]);
                }
            }

            $transaction->update(['status' => TransactionStatusEnum::Completed]); // Update the transaction status of the refund transaction

            if (isset($transaction->externalTransaction->payload['refund'])) { // Only send the email here when a refund was made
                if ($transaction->externalTransaction->payload['refund']['added_via'] == ParticipantAddedViaEnum::Transfer->value) {
                    $participant = Participant::find($transaction->transactionable_id);
    
                    try {
                        Mail::site()->send(new ParticipantFailedTransferMail($participant, $transaction)); // Notify participant via email about the refund
                    } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                        Log::channel(static::getSite()?->code . 'mailexception')->info("Participant - Transfer - Refund");
                        Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                        dispatch(new ResendEmailJob(new ParticipantFailedTransferMail($participant, $transaction), clientSite()));
                    } catch (\Exception $e) {
                        Log::channel(static::getSite()?->code . 'mailexception')->info("Participant - Transfer - Refund");
                        Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                        dispatch(new ResendEmailJob(new ParticipantFailedTransferMail($participant, $transaction), clientSite()));
                    }
                } else {
                    $extraData = [
                        'passed' => $transaction->externalTransaction->payload['passed'],
                        'failed' => [],
                        'wasRecentlyCreated' => $transaction->externalTransaction->payload['wasRecentlyCreated']
                    ];

                    try {
                        $invoice = isset($email) ? $transaction->refresh() : $transaction->transactionable?->refresh(); // If email is set, it means the refund transactionable_type and transactionable_id were not associated with an entity as it couldn't be linked to a user.
                        event(new ParticipantNewRegistrationsEvent($email ?? $user, $extraData, $invoice, clientSite())); // Notify participant via email
                    } catch (Exception $e) { // Issues at the level of the email are less dangerous as the process should have completed
                        Log::channel($this->site->code . 'stripecharge')->info("Participant Registration Exception. Unable to process participant registration mail. " . $e->getMessage());
                        Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Registration Exception. Unable to process participant registration mail. " . $e->getMessage());
                        Log::channel($this->site->code . 'adminanddeveloper')->info($e);
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $exception = true;
            Log::channel($this->site->code . 'stripecharge')->info("Rolling back refund post transaction: " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Rolling back refund post transaction: " . $e->getMessage());
            Log::channel($this->site->code . 'stripecharge')->debug($e);
            Log::channel($this->site->code . 'adminanddeveloper')->debug($e);
            $transaction->update(['status' => TransactionStatusEnum::Failed]); // Update the transaction status to failed
        }

        $data = [];

        if (isset($transaction->externalTransaction->payload['eecs'])) {
            $data['eecs'] = $transaction->externalTransaction->payload['eecs'];
        }

        $transaction->externalTransaction->ongoingExternalTransaction->update([
            'state' => isset($exception) ? OngoingExternalTransactionStateEnum::Failed : OngoingExternalTransactionStateEnum::Completed,
            'status' => isset($exception) ? OngoingExternalTransactionStatusEnum::Failed : OngoingExternalTransactionStatusEnum::Successful,
            'response' => [
                'state' => isset($exception) ? OngoingExternalTransactionStateEnum::Failed : OngoingExternalTransactionStateEnum::Completed,
                'status' => isset($exception) ? OngoingExternalTransactionStatusEnum::Failed : OngoingExternalTransactionStatusEnum::Successful,
                'message' => $transaction->externalTransaction->payload['refund']['message'] ?? null,
                'reason' => $transaction->externalTransaction->payload['refund']['reason'] ?? null,
                ...$data
            ]
        ]);
    }

    /**
     * Compute the amount to pay
     * 
     * @param  mixed  $eecs
     * @param  bool   $cents // Whether to convert the amount to cents or not
     * @return float
     */
    public function amount(mixed $eecs, bool $cents): float
    {
        $amount = 0;

        foreach ($eecs as $eec) {
            $amount += $eec['registration_fee'];
        }

        return FormatNumber::convertAmount($amount, $cents ? CurrencyEnum::Cents : CurrencyEnum::GBP, false, true, 1);
    }

    /**
     * Compute the amount to pay
     * 
     * @param  mixed  $eecs
     * @param  bool   $cents // Whether to convert the amount to cents or not
     * @param array   $qty
     * @return float
    */
    public function amountQty(mixed $eecs, bool $cents, array $qtys): float
    {
        $amount = 0;

        foreach ($eecs as $key => $eec) {
            if ($eec['ref'] == $qtys[$key]['ref']) {
                $qty2 = isset($qtys[$key]['qty']) ? $qtys[$key]['qty'] : 1; // Default to 1 if qty is not set        
                $amount += $eec['registration_fee'] * $qty2;
            }
        }

        return FormatNumber::convertAmount($amount, $cents ? CurrencyEnum::Cents : CurrencyEnum::GBP, false, true, 1);
    }

    /**
     * Process post payment response
     * 
     * @param  Request                     $request
     * @param  OngoingExternalTransaction  $ongoingExternalTransaction
     * @return object
     */
    public function postPaymentResponse(Request $request, OngoingExternalTransaction $ongoingExternalTransaction): object
    {
        $ongoingExternalTransaction->refresh();

        if ($ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Pending || $ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Processing) {
            $start = microtime(true);
            $limit = 25;  // Seconds

            do {
                $ongoingExternalTransaction->refresh();
                sleep(5);
                
                if (microtime(true) - $start >= $limit) {
                    break;
                }
            } while($ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Pending || $ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Processing);
        }

        return (object) [
            'ongoing_external_transaction' => $ongoingExternalTransaction,
            'message' => $ongoingExternalTransaction->response['message'] ?? 'Processing'
        ];
    }

    /**
     * @return array
     */
    public function metaData(): array
    {
       return [
            'added_via' => ParticipantAddedViaEnum::_options(),
            'genders' => GenderEnum::_options()
        ];
    }

    protected function getWalletBalance($user_id): float
    {
        try {
            $balance = Account::where('type', AccountTypeEnum::Infinite)
                ->whereHas('wallet', function ($query) use ($user_id) {
                    $query->whereHasMorph('walletable', [ParticipantProfile::class], function ($query) use ($user_id) {
                        $query->whereHas('profile', function ($query) use ($user_id) {
                            $query->where('user_id', $user_id);
                        });
                    });
                })
                ->value('balance');

            return (float) $balance;
        } catch (Exception $e) {
            throw new Exception("Error fetching wallet balance: " . $e->getMessage());
        }
    }

    public function payCheckoutWallet(Request $request, OngoingExternalTransaction $ongoingExternalTransaction): array
    {
        $prur = new ParticipantRegistrationUpdateRequest();
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $prur->rules(), $prur->messages());

        if ($validator->fails()) {
            return $prur->failedValidation($validator);
        }

        try {
            $amount = $request->filled('added_via') && $request->added_via == ParticipantAddedViaEnum::Transfer->value
                ? FormatNumber::convertAmount($ongoingExternalTransaction->amount, CurrencyEnum::GBP)
                : $this->amount(
                    EventEventCategory::select('id', 'ref', 'event_id', 'event_category_id', 'local_fee', 'international_fee')
                        ->whereIn('ref', collect($request->eec)->pluck('ref'))
                        ->get()
                        ->toArray(),
                    true
                );
            $amount = FormatNumber::convertAmount($amount, CurrencyEnum::GBP, true);
            $amount = str_replace(['£', ','], '', $amount);
            $user_id = $ongoingExternalTransaction->user_id;
            $balance = $this->getWalletBalance($user_id);
            if ($balance < $amount) {
                throw new Exception("Insufficient wallet balance.");
            }

            // Retrieve the wallet account
            $wallet = Account::where('type', AccountTypeEnum::Infinite)
                ->whereHas('wallet', function ($query) use ($user_id) { // Add 'use ($user_id)'
                    $query->whereHasMorph('walletable', [ParticipantProfile::class], function ($query) use ($user_id) { // Add 'use ($user_id)'
                        $query->whereHas('profile', function ($query) use ($user_id) { // Add 'use ($user_id)'
                            $query->where('user_id', $user_id);
                        });
                    });
                })
                ->firstOrFail();

            $computeInvoicePrice = isset($amount) ? false : true;

            $ee_category = EventEventCategory::select('id', 'ref', 'event_id', 'event_category_id', 'local_fee', 'international_fee')
            ->whereIn('ref', collect($request->eec)->pluck('ref'))
            ->first()
            ->toArray();
            $participant = new Participant();
            $participant->ref = Str::orderedUuid()->toString();
            $participant->event_event_category_id = $ee_category['id'];
            $participant->status = 'notified';
            $participant->added_via = 'book_events';
            $participant->user_id = $ongoingExternalTransaction->user_id;
            $participant->save();
            
            $invoice = new Invoice();
            $invoice->forceFill([
                'ref' => Str::orderedUuid()->toString(),
                'site_id' => $ongoingExternalTransaction->site_id,
                'invoiceable_id' => $ongoingExternalTransaction->user_id,
                'invoiceable_type' => User::class,
                'name' => 'Invoice for Participant Registration',
                'issue_date' => Carbon::now(),
                'due_date' => Carbon::now(),
                'price' => $amount,
                'compute' => $computeInvoicePrice,
                'status' => InvoiceStatusEnum::Paid,
                'state' => InvoiceStateEnum::Complete,
                'send_on' => Carbon::now(),
                'description' => 'participant pay via wallet'
            ]);
            $invoice->saveQuietly();

            $invoiceItem = new InvoiceItem();
            $invoiceItem->forceFill([
                'ref' => Str::orderedUuid()->toString(),
                'invoice_id' => $invoice->id,
                'invoice_itemable_id' => $participant->id,
                'invoice_itemable_type' => Participant::class,
                'price' => $amount,
                'type' => InvoiceItemTypeEnum::ParticipantRegistration,
                'status' => InvoiceItemStatusEnum::Paid
            ]);
            $invoiceItem->saveQuietly();

            $transactionId = DB::table('transactions')->insertGetId([
                'site_id' => $ongoingExternalTransaction->site_id,
                'user_id' => $ongoingExternalTransaction->user_id,
                'ref' => Str::orderedUuid()->toString(),
                'transactionable_type' => Account::class,
                'transactionable_id' => $invoice->id,
                'amount' => $amount,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('internal_transactions')->insert([
                'account_id' => $wallet->id,
                'transaction_id' => $transactionId,
                'ref' => Str::orderedUuid()->toString(),
                'amount' => $amount,
                'type' => 'debit',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Deduct the amount and save the updated wallet balance
            $wallet->decrement('balance', $amount);

            $ongoingExternalTransaction->update([
                'amount' => $amount,
                'email' => $request->user['email'] ?? null,
                'user_id' => $user_id,
                'description' => 'Pay Via Wallet',
                'payment_intent_id' => 'Pay Via Wallet',
                'status' => OngoingExternalTransactionStatusEnum::Successful,
                'payload' => ['payload' => $request->all()],
            ]);

            return [
                'payment_required' => true,
                'proceed_to_payment' => true,
                'ongoing_external_transaction' => $ongoingExternalTransaction,
                'amount' => FormatNumber::convertAmount($amount, CurrencyEnum::GBP, true),
                'currency' => CurrencyEnum::GBP->value,
                'message' => 'Payment processed using wallet!',
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function postPaymentWalletResponse(Request $request, OngoingExternalTransaction $ongoingExternalTransaction): object
    {
        $ongoingExternalTransaction->refresh();

        if ($ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Pending || $ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Processing) {
            $start = microtime(true);
            $limit = 25;  // Seconds

            do {
                $ongoingExternalTransaction->refresh();
                sleep(5);

                if (microtime(true) - $start >= $limit) {
                    break;
                }
            } while($ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Pending || $ongoingExternalTransaction->status == OngoingExternalTransactionStatusEnum::Processing);
        }

        return (object) [
            'ongoing_external_transaction' => $ongoingExternalTransaction,
            'message' => $ongoingExternalTransaction->response['message'] ?? 'Processing'
        ];
    }
}