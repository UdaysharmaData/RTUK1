<?php

namespace App\Services\Payment\Traits;

use Exception;
use Stripe\Webhook;
use App\Enums\CurrencyEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Helpers\FormatNumber;
use App\Modules\Finance\Enums\OngoingExternalTransactionStateEnum;
use App\Modules\Finance\Enums\TransactionStatusEnum;
use App\Modules\Finance\Enums\OngoingExternalTransactionStatusEnum;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\OngoingExternalTransaction;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait PaymentTrait {

    /**
     * Fetch webhook data
     * 
     * @param  mixed   $request
     * @param  string  $secretKey
     * @return object
     */
    public function fetchWebhookData(mixed $request, $secretKey): object
    {
        $payload = $request->getContent();
        $sig_header = $request->header("Stripe-Signature");

        try {
            return Webhook::constructEvent(
                $payload, $sig_header, $secretKey
            );
        } catch(\UnexpectedValueException $e) {
            $_payload = json_decode($payload);
            $message = "Invalid payload - " . json_encode(['id' => $_payload->data->object->id, 'object' => $_payload->data->object->object]);
            Log::channel($this->site->code . 'stripe')->error($message);
            throw new Exception($message);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            $_payload = json_decode($payload);
            $message = "Invalid signature - " . json_encode(['id' => $_payload->data->object->id, 'object' => $_payload->data->object->object]);
            Log::channel($this->site->code . 'stripe')->error($message);
            throw new Exception($message);
        }
    }

    /**
     * Handle payment intent create success.
     * 
     * @param  object  $paymentIntent
     * @param  object  $result
     * @return void
     */
    public function paymentIntentSucceeded(object $paymentIntent, object $result): void
    {
        Log::channel($this->site->code . 'stripepaymentintent')->info(($paymentIntent?->user?->full_name ?? $paymentIntent?->email) . ' was charged ' . FormatNumber::convertAmount($result->amount, CurrencyEnum::GBP, true));
        Log::channel($this->site->code . 'stripepaymentintent')->info("Payment Intent Retrieved Successfully! - " . json_encode(['id' => $paymentIntent->id, 'payment_intent_id' => $paymentIntent->payment_intent_id]));
    }

    /**
     * Handle payment intent create failure.
     * 
     * @param  object  $paymentIntent
     * @param  object  $result
     * @return void
     */
    public function paymentIntentFailed(object $paymentIntent, object $result): void
    {
        $message = "Payment Intent Failure! - " . $paymentIntent->id;
        Log::channel($this->site->code . 'stripepaymentintent')->info('An attempt to fulfill the payment ' . $paymentIntent->payment_intent_id . ' for ' . FormatNumber::convertAmount($result->amount, CurrencyEnum::GBP, true). ' failed!');
        Log::channel($this->site->code . 'stripepaymentintent')->info($message);
        throw new Exception($message);
        // TODO: Log to developers channel
    }

    /**
     * Check if payment intent exists and it is still under processing (pending payment)
     * 
     * @param  string          $paymentIntentId
     * @param  string|null     $type            // The type of event triggered
     * @return OngoingExternalTransaction
     */
    public function findPaymentIntent(string $paymentIntentId, ?string $type = null): OngoingExternalTransaction
    {
        try {
            $ongoingExternalTransaction = OngoingExternalTransaction::with('user')
                ->where('payment_intent_id', $paymentIntentId)
                // ->when($type == 'charge.refunded' || $type = 'payment_intent.payment_failed', function ($query) {
                //     return $query->withTrashed();
                // })
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            $message = "Payment Intent Not Found - " . $paymentIntentId;
            Log::channel($this->site->code . 'stripepaymentintent')->info($message);
            throw new Exception($message);
        }

        return $ongoingExternalTransaction;
    }

    /**
     * Handle charge success.
     * 
     * @param  OngoingExternalTransaction  $ongoingExternalTransaction
     * @param  object                      $charge
     * @return OngoingExternalTransaction
     */
    public function chargeSucceeded(OngoingExternalTransaction $ongoingExternalTransaction, object $charge): OngoingExternalTransaction
    {
        // $payment = Payment::create([
        //     'payment_intent_id' => $ongoingExternalTransaction->payment_intent_id,
        //     'payload' => $ongoingExternalTransaction->payload,
        //     'status' => TransactionStatusEnum::Paid
        // ]);

        // $ongoingExternalTransaction->delete(); // Delete the payment intent(pending payment)

        Log::channel($this->site->code . 'stripecharge')->info(($ongoingExternalTransaction?->user?->full_name ?? $ongoingExternalTransaction?->email) . ' was charged ' . FormatNumber::convertAmount($charge->amount, CurrencyEnum::GBP, true));
        // Log::channel($this->site->code . 'stripecharge')->info($charge);

        // $ongoingExternalTransaction->update([
        //     'status' => OngoingExternalTransactionStatusEnum::Successful,
        //     'response' => [
        //         'status' => true,
        //         'message' => 'The charge has been captured!'
        //     ]
        // ]);

        Log::channel($this->site->code . 'stripecharge')->info("The Charge Was Successful - " . $ongoingExternalTransaction);

        return $ongoingExternalTransaction;
    }

    /**
     * Handle charge failure.
     * 
     * @param  OngoingExternalTransaction  $ongoingExternalTransaction
     * @param  object                      $charge
     * @return void
     */
    public function chargeFailed(OngoingExternalTransaction $ongoingExternalTransaction, object $charge): void
    {
        // Transaction::create([
        //     'payment_intent_id' => $ongoingExternalTransaction->payment_intent_id,
        //     'payload' => $ongoingExternalTransaction->payload,
        //     'email' => $ongoingExternalTransaction->payload['user']['email'],
        //     'status' => TransactionStatusEnum::Failed
        // ]);

        // $ongoingExternalTransaction->delete(); // Delete the payment intent(pending payment) // TODO: @tsaffi Write a command to delete failed payments older than 7 days and run it daily. Also ensure it deletes records where deleted_at is not null on daily basis

        Log::channel($this->site->code . 'stripecharge')->info(($ongoingExternalTransaction?->user?->full_name ?? $ongoingExternalTransaction?->email) . ' payment for ' . FormatNumber::convertAmount($charge->amount, CurrencyEnum::GBP, true) . ' failed');
        // Log::channel($this->site->code . 'stripecharge')->info($charge);

        $ongoingExternalTransaction->update([
            'state' => OngoingExternalTransactionStateEnum::Completed,
            'status' => OngoingExternalTransactionStatusEnum::Failed,
            'response' => [
                'status' => true,
                'code' => $charge->failure_code,
                'message' => $charge->failure_message
            ]
        ]);

        Log::channel($this->site->code . 'stripecharge')->info("The Charge Failed: " . $ongoingExternalTransaction);
    }

    /**
     * Get customer for the stripe payment
     * 
     * @param  Request  $request
     * @return array
     */
    public function getOrCreateCustomer(Request $request): array
    {
        $data = [];

        if (isset($request->user['email'])) {
            if ($user = User::where('email', $request->user['email'])->first()) {
                $data['user'] = $user;

                if ($user->stripe_customer_id) {
                    $customer = $this->stripe->customers->retrieve($user->stripe_customer_id);

                    if (!$customer) {
                        $customer = $this->stripe->customers->create([
                            'name' => $user->full_name,
                            'email' => $user->email
                        ]);
                        Log::channel($this->site->code . 'stripepaymentintent')->info("{$request->user['email']} is a new customer");
                        $user->update(['stripe_customer_id' => $customer->id]);
                    }
                    $data['customer'] = $customer->id;
                } else {
                    $customer = $this->stripe->customers->create([
                        'name' => $user->full_name,
                        'email' => $request->user['email']
                    ]);
                    $data['customer'] = $customer->id;
                    Log::channel($this->site->code . 'stripepaymentintent')->info("{$request->user['email']} is a new customer");
                    $user->update(['stripe_customer_id' => $customer->id]);
                }
            } else {
                $customer = $this->stripe->customers->create([
                    'name' => 'Guest | ' . ($request->filled('user') ? (isset($request->user['first_name']) || isset($request->user['last_name']) ? $request->user['first_name'] . ' ' . $request->user['last_name'] : static::getSite()?->name) : static::getSite()?->name),
                    'email' => $request->user['email']
                ]);
                Log::channel($this->site->code . 'stripepaymentintent')->info("{$request->user['email']} is a new customer");
                $data['customer'] = $customer->id;
            }
        }

        return $data;
    }
}