<?php

namespace App\Jobs;

use Exception;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Finance\Enums\PaymentTypeEnum;
use App\Services\Payment\ParticipantTransferPayment;
use App\Modules\Finance\Models\OngoingExternalTransaction;
use App\Services\Payment\ParticipantRegistrationPayment;

class handleStripePostPaymentWithoutWebhookJob
{
    use Dispatchable, SerializesModels;

    public $transactions;

    public Site $site;

    public $stripe;

    public $stripeSecretKey;

    public $participantRegistrationPayment;

    public $participantTransferPayment;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Create a new job instance.
     * 
     * @param  mixed  $transactions
     * @param  Site   $site
     * @return void
     */
    public function __construct(mixed $transactions, Site $site)
    {
        $this->transactions = $transactions;
        $this->site = $site;

        $this->stripeSecretKey = config('stripe.' . $this->site->code . '.secret_key');

        $this->stripe = new StripeClient($this->stripeSecretKey);

        $this->participantRegistrationPayment = new ParticipantRegistrationPayment($this->stripe, $this->site);
        $this->participantTransferPayment = new ParticipantTransferPayment($this->stripe, $this->site);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $pid = getmypid();

            Cache::put('command-site-' . $pid,  $this->site, now()->addHour());

            foreach ($this->transactions as $transaction) {
                $paymentIntent = $this->stripe->paymentIntents->retrieve($transaction->payment_intent_id); // Get the payment intent
        
                if ($paymentIntent->status == 'succeeded') {
                    $this->processTransaction($transaction, $paymentIntent);
                } else {
                    Log::channel('test')->debug('Payment status: ' . $paymentIntent->status);
                    Log::channel('test')->debug($paymentIntent);
                }
            }

            Cache::forget('command-site-' . $pid);
        } catch (Exception $e) {
            Cache::forget('command-site-' . $pid);
            Log::channel('test')->debug('Exception : Handle stripe post payment without webhook');
            Log::channel('test')->debug($e);
        }
    }

    /**
     * Process the transaction
     *
     * @param  OngoingExternalTransaction  $transaction
     * @param  object                      $paymentIntent
     * @return void
     */
    public function processTransaction(OngoingExternalTransaction $transaction, object $paymentIntent): void
    {
        $type = PaymentTypeEnum::tryFrom($paymentIntent->metadata->type);

        Log::channel('test')->debug('paymentIntent command log');
        Log::channel('test')->debug(json_encode($paymentIntent));
        $paymentIntent['job'] = true;

        switch ($type) {
            case PaymentTypeEnum::ParticipantRegistration:
                $result = $this->participantRegistrationPayment->processCharge($paymentIntent);
                break;
            case PaymentTypeEnum::ParticipantTransfer:
                $result = $this->participantTransferPayment->processCharge($paymentIntent);
                break;
            default:
                Log::channel('test')->debug('Received unknown payment intent event type: ' . $type);
                Log::channel('test')->debug(json_encode($paymentIntent));
        }
    }
}
