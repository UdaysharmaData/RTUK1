<?php

namespace App\Services\Payment;

use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Payment\Contracts\PaymentInterface;

class CharityMembershipPayment extends Payment implements PaymentInterface
{
    /**
     * Handle charge webhook events
     * 
     * @param  mixed  $request
     * @return void
     */
    public function createPaymentIntent(mixed $request): void
    {

    }

    /**
     * Handle payment method webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentMethod(mixed $request): object
    {
        $paymentMethod = parent::processPaymentMethod($request);

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
        $charge = parent::processCharge($request);

        return $charge;
    }

    /**
     * Compute the amount to pay
     * 
     * @param  mixed  $request
     * @return float
     */
    public function amount(mixed $request): float
    {
        return 15.5 * 100;
    }
}