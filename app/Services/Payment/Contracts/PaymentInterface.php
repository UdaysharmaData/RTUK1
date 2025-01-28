<?php

namespace App\Services\Payment\Contracts;

use App\Modules\Finance\Models\OngoingExternalTransaction;

interface PaymentInterface
{
    /**
     * @param  mixed  $request
     * @return array
     */
    // public function createPaymentIntent(ParticipantRegistrationCreateRequest|EventCreateRequest $request): array;
    // public function createPaymentIntent(mixed $request): array;

    /**
     * Handle payment intent webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentIntent(mixed $request): object;

    /**
     * Handle payment method webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentMethod(mixed $request): object;

    /**
     * Handle payment link webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processPaymentLink(mixed $request): object;

    /**
     * Handle charge webhook events
     * 
     * @param  mixed  $request
     * @return object
     */
    public function processCharge(mixed $request): object;

    /**
     * Validate the payload to ensure the correctness of the data
     * 
     * @param  array  $payload
     * @return void
     */
    public function validatePayload(array $payload): void;

    /**
     * Process the payload
     * 
     * @param  OngoingExternalTransaction  $ongoingExternalTransaction
     * @param  object          $chargeId
     * @return object
     */
    public function processPayload(OngoingExternalTransaction $ongoingExternalTransaction, object $charge): object;

    /**
     * Compute the amount to pay
     * 
     * @param  mixed  $request
     * @param  bool   $cents
     * @return float
     */
    public function amount(mixed $request, bool $cents): float;

}
