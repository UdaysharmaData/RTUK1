<?php

namespace App\Services\Payment;

use DB;
use Log;
use Str;
use Exception;
use Validator;
use Carbon\Carbon;
use App\Traits\Response;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Exceptions\MailException;
use App\Http\Helpers\FormatNumber;
use App\Services\Payment\Contracts\PaymentInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Modules\Finance\Requests\ParticipantTransferCreateRequest;
use App\Modules\Finance\Requests\ParticipantTransferUpdateRequest;

use App\Modules\Finance\Models\Transaction;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Finance\Models\OngoingExternalTransaction;

use App\Enums\GenderEnum;
use App\Enums\CurrencyEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Modules\Finance\Enums\PaymentTypeEnum;
use App\Modules\Finance\Enums\TransactionTypeEnum;
use App\Modules\Finance\Enums\TransactionStatusEnum;
use App\Modules\Finance\Enums\PaymentMethodsTypesEnum;
use App\Modules\Finance\Enums\TransactionPaymentMethodEnum;
use App\Modules\Finance\Enums\OngoingExternalTransactionStatusEnum;

use App\Traits\SiteTrait;
use App\Modules\Event\Exceptions\EventEventCategoryException;
use App\Modules\Finance\Enums\OngoingExternalTransactionStateEnum;
use App\Modules\Participant\Exceptions\IsRegisteredException;
use App\Modules\Participant\Exceptions\ParticipantTransferException;

class ParticipantTransferPayment extends Payment implements PaymentInterface
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
        $ptcr = new ParticipantTransferCreateRequest();
        $validator = Validator::make($request->all(), $ptcr->rules(), $ptcr->messages());

        if ($validator->fails()) { // Validate the request
            Log::channel($this->site->code . 'adminanddeveloper')->info('Invalid payload: ' . json_encode($validator->errors()));
            Log::channel($this->site->code . 'adminanddeveloper')->info('Payload: ' . json_encode($request->all()));
            Log::channel($this->site->code . 'stripecharge')->info('Invalid payload: ' . json_encode($validator->errors()));
            return $ptcr->failedValidation($validator);
        }

        try {
            $participant = Participant::with(['eventEventCategory.event', 'eventEventCategory.eventCategory', 'user'])
                ->where('ref', $request['participant'])
                ->firstOrFail();

            $eec = EventEventCategory::where('ref', $request->eec[0]['ref'])->first();

            $validation = Participant::validateTransfer($participant, $eec, $participant->user);

            if (!$validation['payment_required']) { // Payment must be required to checkout
                Log::channel($this->site->code . 'adminanddeveloper')->debug('Payment must be required to proceed to checkout!');
                throw new Exception('Payment must be required to proceed to checkout!');
            }

            $amount = $this->amount($validation['total'], true);

            if ($amount > 30) { // Stripe Exception - Amount must be at least £0.30 gbp
                $request['amount'] = $amount;
                return [...$this->createPaymentIntent($request), ...$this->metaData()];
            }
        } catch (ModelNotFoundException $e) {
            throw new Exception('The entry was not found!');
        } catch (ParticipantTransferException $e) {
            $message = str_replace('participant', 'entry', $e->getMessage());
            throw new Exception($message);
        } catch (EventEventCategoryException $e) {
            $message = str_replace('participant', 'entry', $e->getMessage());
            throw new Exception($message);
        } catch (IsRegisteredException $e) {
            $message = str_replace('participant', 'entry', $e->getMessage());
            throw new Exception($e->getMessage());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return [
            'payment_required' => true,
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
        $ptcr = new ParticipantTransferCreateRequest();
        $validator = Validator::make($request->all(), $ptcr->rules(), $ptcr->messages());

        if ($validator->fails()) { // Validate the request
            Log::channel($this->site->code . 'adminanddeveloper')->info('Invalid payload: ' . json_encode($validator->errors()));
            Log::channel($this->site->code . 'adminanddeveloper')->info('Payload: ' . json_encode($request->all()));
            Log::channel($this->site->code . 'stripecharge')->info('Invalid payload: ' . json_encode($validator->errors()));
            return $ptcr->failedValidation($validator);
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
        $ptur = new ParticipantTransferUpdateRequest();
        $validator = Validator::make($request->all(), $ptur->rules(), $ptur->messages());

        if ($validator->fails()) { // Validate the request
            Log::channel($this->site->code . 'adminanddeveloper')->info('Invalid payload: ' . json_encode($validator->errors()));
            Log::channel($this->site->code . 'adminanddeveloper')->info('Payload: ' . json_encode($request->all()));
            Log::channel($this->site->code . 'stripecharge')->info('Invalid payload: ' . json_encode($validator->errors()));
            return $ptur->failedValidation($validator);
        }

        try {
            $amount = $ongoingExternalTransaction->amount;

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
                    'amount' => (float) $amount,
                    'metadata' => [
                        'X-Client-Key' => $request->header('X-Client-Key'), // Set the X-Client-Key
                        'type' => PaymentTypeEnum::ParticipantRegistration->value
                    ],
                    ...$data
                ]);

                $ongoingExternalTransaction->update([
                    'email' => isset($request->user) && isset($request->user['email']) ? $request->user['email']: null,
                    'user_id' => $user?->id,
                    'payload' => [
                        'payload' => [...$request->all(), 'amount' => $amount]
                    ]
                ]);
            } else {
                Log::channel($this->site->code . 'adminanddeveloper')->info("Wrong endpoint! The amount should not be less than £0.3.");
                throw new Exception("Wrong endpoint! The amount should not be less than £0.3.");
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
     * Handle payment method webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentMethod(mixed $request): object
    {
        return (new ParticipantRegistrationPayment($this->stripe, $this->site))->processPaymentMethod($request);
    }

    /**
     * Handle payment link webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentLink(mixed $request): object
    {
        return (new ParticipantRegistrationPayment($this->stripe, $this->site))->processPaymentLink($request);
    }

    /**
     * Handle charge webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processCharge(mixed $request): object
    {
        return (new ParticipantRegistrationPayment($this->stripe, $this->site))->processCharge($request);
    }

    /**
     * Validate Payload
     * 
     * @param  array  $payload
     * @return void
     */
    public function validatePayload(array $payload): void
    {

    }

    /**
     * Process Payload - Transfer the participant
     * 
     * @param  OngoingExternalTransaction  $ongoingExternalTransaction
     * @param  object                      $charge
     * @return object
     */
    public function processPayload(OngoingExternalTransaction $ongoingExternalTransaction, object $charge): object
    {
        return (object) [];
    }

    /**
     * Compute the amount to pay
     * 
     * @param  mixed  $amount
     * @param  bool   $cents // Whether to convert the amount to cents or not
     * @return float
     */
    public function amount(mixed $amount, bool $cents): float
    {
        return FormatNumber::convertAmount($amount, $cents ? CurrencyEnum::Cents : CurrencyEnum::GBP, false, true, 1);
    }

    /**
     * Process participant transfer post payment
     * 
     * @param  OngoingExternalTransaction $ongoingExternalTransaction
     * @param  object                     $charge
     * @param  EventEventCategory         $eec
     * @param  array                      $payload
     * @return object
     */
    public function processParticipantTransfer(OngoingExternalTransaction $ongoingExternalTransaction, object $charge, EventEventCategory $eec, array $payload): object
    {
        try {
            $participant = Participant::with(['eventEventCategory.event', 'eventEventCategory.eventCategory', 'user'])
                ->where('ref', $payload['participant'])
                ->firstOrFail();

            Log::channel($this->site->code . 'stripecharge')->info("Participant Transfer - Processing transfer of participant (" . $participant->user->full_name . ") from " . $participant->eventEventCategory->event->name . " (" . $participant->eventEventCategory->eventCategory?->name . ")" .  " to " . $eec->event->name . " (" . $eec->eventCategory?->name . ")");

            $validation = Participant::validateTransfer($participant, $eec, $participant->user);
            $validation['ongoingExternalTransaction'] = $ongoingExternalTransaction;
            $validation['charge'] = $charge;
            $validation['paymentMethod'] = $charge->payment_method_details->type;
            $result = (object) Participant::processTransfer($participant, $eec, $validation);

            Log::channel($this->site->code . 'stripecharge')->info("Participant Transfer - Successful transfer of participant (" . $participant->user->full_name . ") from " . $participant->eventEventCategory->event->name . " (" . $participant->eventEventCategory->eventCategory?->name . ")" .  " to " . $eec->event->name . " (" . $eec->eventCategory?->name . ")");

            $ongoingExternalTransaction->update([
                'state' => OngoingExternalTransactionStateEnum::Completed,
                'status' => OngoingExternalTransactionStatusEnum::Successful,
                'response' => [
                    'state' => OngoingExternalTransactionStateEnum::Completed,
                    'status' => OngoingExternalTransactionStatusEnum::Successful,
                    'message' => $result->message
                ]
            ]);

            return $result;
        } catch (ModelNotFoundException $e) {
            Log::channel($this->site->code . 'stripecharge')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . ") failed. The participant was not found!");
            Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . ") failed. The participant was not found!");
            Log::channel($this->site->code . 'adminanddeveloper')->info($e);
            $message = "The participant was not found!";
        } catch (ParticipantTransferException $e) {
            Log::channel($this->site->code . 'stripecharge')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info($e);
            $message = $e->getMessage();
        } catch (EventEventCategoryException $e) {
            Log::channel($this->site->code . 'stripecharge')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info($e);
            $message = $e->getMessage();
        } catch (IsRegisteredException $e) {
            Log::channel($this->site->code . 'stripecharge')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info($e);
            $message = $e->getMessage();
        } catch (Exception $e) {
            Log::channel($this->site->code . 'stripecharge')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Transfer Exception. Unable to transfer participant (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info($e);
            $message = $e->getMessage();
        } catch (MailException $e) { // Issues at the level of the email are less dangerous as the process should have completed
            Log::channel($this->site->code . 'stripecharge')->info("Participant Transfer Exception. Unable to process participant transfer mail (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Participant Transfer Exception. Unable to process participant transfer mail (" . $payload['participant'] .  ") to " . $eec->event->name . " (" . $eec->eventCategory?->name . "). " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info($e);

            $participantOrEntry = ReplaceTextHelper::participantOrEntry();

            return (object) [
                'status' => true,
                'message' => "The $participantOrEntry has been successfully transferred!"
            ];
        }

        $transaction = Transaction::create([ // Create a payment transaction for the invoice
            'ongoing_external_transaction_id' => $ongoingExternalTransaction->id,
            'transactionable_id' => $participant->id,
            'transactionable_type' => Participant::class,
            'user_id' => $ongoingExternalTransaction->user_id,
            'status' => TransactionStatusEnum::Failed,
            'type' => TransactionTypeEnum::Transfer,
            'amount' => FormatNumber::convertAmount($charge->amount, CurrencyEnum::GBP),
            'payment_method' => $charge->payment_method_details->type,
            'description' => 'Extra Payment for participant transfer from ' . $participant->event->name . '(' . $participant->eventEventCategory->eventCategory?->name . ') to ' . $eec->event->name . '(' . $eec->eventCategory?->name . ')'
        ]);

        $externalTransaction = $transaction->externalTransaction()->create([ // Create an external transaction for the payment
            'payment_intent_id' => $ongoingExternalTransaction->payment_intent_id,
            'charge_id' => $charge->id,
            'payload' => $ongoingExternalTransaction->payload
        ]);

        $failed = [];
        $failed['type'] = "full"; // Make a full refund
        $failed['added_via'] = $payload['added_via'];
        $failed['amount'] = $charge->amount; // The amount to be refunded
        $failed['description'] = 'Refund for participant transfer extra payment from ' . $participant->event->name . '(' . $participant->eventEventCategory->eventCategory?->name . ') to ' . $eec->event->name . '(' . $eec->eventCategory?->name . ')';
        $failed['user'] = $payload['user'];
        $failed['transactionable_id'] = $participant->id; // The id of the participant associated with the payment
        $failed['transactionable_type'] = Participant::class;
        $failed['message'] = "Transfer Failed! We could not process the transfer of the entry and you were refunded. Please check your emails for more details.";
        $failed['reason'] = $message;
        $failed['old_eec'] = [
            'ref' => $participant->eventEventCategory->ref,
            'event' => [
                'name' => $participant->eventEventCategory->event->formattedName
            ],
            'category' => [
                'name' => $participant->eventEventCategory->eventCategory?->name
            ]
        ];
        $failed['new_eec'] = [
            'ref' => $eec->ref,
            'event' => [
                'name' => $eec->event->formattedName
            ],
            'category' => [
                'name' => $eec->eventCategory?->name
            ]
        ];

        (new ParticipantRegistrationPayment($this->stripe, $this->site))->processPostPaymentRefund($ongoingExternalTransaction, [], $failed, [], $charge, $failed['reason'], "full");

        return (object) [
            'status' => false,
            'message' => $failed['message'],
            'reason' => $failed['reason']
        ];
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
            $limit = 60;  // Seconds

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
}