<?php

namespace App\Services\Payment;

use Log;
use Exception;
use Stripe\StripeClient;
use App\Enums\CurrencyEnum;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\FormatNumber;
use App\Modules\Setting\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Payment\Traits\PaymentTrait;
use App\Services\Payment\Contracts\PaymentInterface;

class Payment
{
    use PaymentTrait;

    public $stripe;
    public $site;

    public function __construct(StripeClient $stripe, Site $site)
    {
        $this->stripe = $stripe;
        $this->site = $site;

        \Log::debug("Payment Constructor Ran");
    }

    /**
     * Handle payment intent webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentIntent(mixed $request): object
    {
        $event = $this->fetchWebhookData($request, config('stripe.' . $this->site->code . '.webhook.secret.payment_intent'));

        $paymentIntent = $this->findPaymentIntent($event->data->object->id, $event->type);

        switch ($event->type) {
            case 'payment_intent.created':
                Log::channel($this->site->code . 'stripepaymentintent')->info("A new payment {$paymentIntent->payment_intent_id} for " . FormatNumber::convertAmount($event->data->object->amount, CurrencyEnum::GBP, true) . " was created");
                break;
            case 'payment_intent.succeeded':
                $this->paymentIntentSucceeded($paymentIntent, $event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->paymentIntentFailed($paymentIntent, $event->data->object);
                break;
            case 'payment_intent.requires_action':
                Log::channel($this->site->code . 'stripepaymentintent')->info("The payment {$paymentIntent->payment_intent_id} for ". FormatNumber::convertAmount($event->data->object->amount, CurrencyEnum::GBP, true) . " requires you to take action in order to complete the payment!");
                break;
            case 'payment_intent.partially_funded':
                Log::channel($this->site->code . 'stripepaymentintent')->info('Payment intent partially funded!');
                break;
            case 'payment_intent.processing':
                Log::channel($this->site->code . 'stripepaymentintent')->info('Payment intent processing!');
                break;
            case 'payment_intent.canceled':
                Log::channel($this->site->code . 'stripepaymentintent')->info('Payment intent canceled!');
                break;
            default:
                Log::channel($this->site->code . 'stripepaymentintent')->info('Received unknown payment intent event type: ' . $event->type);
                throw new Exception("Unknown payment intent event type!");
                // TODO: Log to developers channel
        }

        return (object) [
            'event' => $event,
            'ongoing_external_transaction' => $paymentIntent
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
        $event = $this->fetchWebhookData($request, config('stripe.' . $this->site->code . '.webhook.secret.payment_method'));

        return (object) [
            'event' => $event,
            // 'ongoing_external_transaction' => $this->findPaymentIntent($event->data->object->payment_intent)
        ];
    }

    /**
     * Handle payment link webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentLink(mixed $request): object
    {
        $event = $this->fetchWebhookData($request, config('stripe.' . $this->site->code . '.webhook.secret.payment_link'));

        return (object) [
            'event' => $event,
            'ongoing_external_transaction' => $this->findPaymentIntent($event->data->object->payment_intent)
        ];
    }

    /**
     * Handle charge webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processCharge(mixed $request): object
    {
        $event = $this->fetchWebhookData($request, config('stripe.' . $this->site->code . '.webhook.secret.charge'));

        return (object) [
            'event' => $event,
            'ongoing_external_transaction' => $this->findPaymentIntent($event->data->object->payment_intent, $event->type)
        ];
    }
}