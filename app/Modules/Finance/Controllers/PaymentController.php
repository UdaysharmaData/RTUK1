<?php

namespace App\Modules\Finance\Controllers;

use Log;
use Auth;
use Exception;
use Stripe\StripeClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;

use App\Enums\ParticipantAddedViaEnum;
use App\Modules\Finance\Enums\OngoingExternalTransactionStatusEnum;
use App\Modules\Finance\Enums\PaymentTypeEnum;
use App\Modules\Finance\Enums\TransactionStatusEnum;

use App\Modules\Finance\Models\Transaction;

use App\Services\Payment\ParticipantTransferPayment;
use App\Services\Payment\ParticipantRegistrationPayment;
use App\Modules\Finance\Models\OngoingExternalTransaction;
use App\Modules\Finance\Requests\ParticipantRegistrationCreateRequest;
use App\Modules\Finance\Requests\ParticipantRegistrationUpdateRequest;
use App\Modules\Finance\Requests\ParticipantTransferCreateRequest;
use App\Modules\Finance\Requests\ParticipantTransferUpdateRequest;
use Illuminate\Support\Facades\Route;

/**
 * @group Payment
 * Handles all payment related operations
 * @unauthenticated
 */
class PaymentController extends Controller
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

    public $currentRequest;

    public $site;

    public $stripe;

    public $stripeSecretKey;

    public $participantRegistrationPayment;

    public $participantTransferPayment;

    public function __construct()
    {
        parent::__construct();

        // $this->middleware('role:can_manage_payments', [
        //     'except' => [

        //     ]
        // ]);  
        if (! request()->header('X-Client-Key') && request('data') && request('data')['object'] && request('data')['object']['metadata'] && request('data')['object']['metadata']['X-Client-Key']) { // Set the client key header for the request
            // $request->headers->set('X-Client-Key', $request->data['object']['metadata']['X-Client-Key']);
            // (new \Illuminate\Http\Request())->headers->add(['X-Client-Key' => request('data')['object']['metadata']['X-Client-Key']]); // NB: Controller constructor always runs before the middleware. That is why we set this here.
            // (new \Illuminate\Http\Request())->headers->set('X-Client-Key', request('data')['object']['metadata']['X-Client-Key']); // NB: Controller constructor always runs before the middleware. That is why we set this here.
            // request()->header('X-Client-Key', request('data')['object']['metadata']['X-Client-Key']);

            $this->currentRequest = request();
            $this->currentRequest->headers->set('X-Client-Key', request('data')['object']['metadata']['X-Client-Key']); // NB: Controller constructor always runs before the middleware. That is why we set this here.

            // Log::debug('header set 237');
            // Log::debug('Client Key: ' . request('data')['object']['metadata']['X-Client-Key']);
            // Log::debug('Client Key: ' . request()->header('X-Client-Key'));
        }

        if (! request()->header('X-Client-Key')) { // If the client key is not set in the request.
            $message = 'Payment Controller - Client Key not set in request!';
            Log::debug($message);
            Log::channel('stripe')->error($message);
            Log::channel('adminanddeveloper')->error($message);
            Log::channel('stripe')->debug('URI: ' . \Illuminate\Support\Facades\Route::getFacadeRoot()->current()->uri());
            Log::channel('adminanddeveloper')->debug('URI: ' . \Illuminate\Support\Facades\Route::getFacadeRoot()->current()->uri());

            throw new HttpResponseException($this->error($message, 406, 'Client Key not set in request!'));
        }

        Log::debug(\Illuminate\Support\Facades\Route::getFacadeRoot()->current()->uri());

        Log::debug('Client Key: ' . request()->header('X-Client-Key'));

        $this->site = static::getSite();

        if (! $this->site) {
            $message = 'Payment Controller - Site not found!';
            Log::debug($message);
            Log::channel('stripe')->error($message);
            Log::channel('adminanddeveloper')->error($message);
            Log::channel('stripe')->error('X-Client-Key: ' . request()->header('X-Client-Key'));
            Log::channel('adminanddeveloper')->error('X-Client-Key: ' . request()->header('X-Client-Key'));

            throw new HttpResponseException($this->error($message, 406, 'Site not found!'));
        }

        // Log::debug("Site: " . $this->site);

        $this->stripeSecretKey = config('stripe.' . $this->site->code . '.secret_key');

        $this->stripe = new StripeClient($this->stripeSecretKey);

        $this->participantRegistrationPayment = new ParticipantRegistrationPayment($this->stripe, $this->site);
        $this->participantTransferPayment = new ParticipantTransferPayment($this->stripe, $this->site);
    }

    /**
     * Get the meta data
     * 
     * @return JsonResponse
     */
    public function metaData(): JsonResponse
    {
        return $this->success('The meta data', 200, [
            'payment_types' => PaymentTypeEnum::_options()
        ]);
    }

    /**
     * Proceed to Checkout
     * 
     * @urlParam type string required The entity payment is made for. Must be one of participant_registration, participant_transfer, market_resale, charity_membership,partner_package_assignment,event_places,corporate_credit.  Example: participant_registration
     * @param  Request              $request
     * @param  PaymentTypeEnum  $type
     * @return JsonResponse
     */
    public function proceedToCheckout(Request $request, PaymentTypeEnum $type): JsonResponse
    {
        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $result = $this->participantRegistrationPayment->proceedToCheckout($request);
                    break;
                case PaymentTypeEnum::ParticipantTransfer:
                    $result = $this->participantTransferPayment->proceedToCheckout($request);
                    break;
                default:
                    $result = $this->participantRegistrationPayment->proceedToCheckout($request);
            }
        } catch (HttpResponseException $e) {
            throw new HttpResponseException($e->getResponse());
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success($result['message'], 200, $result);
    }

    /**
     * Create the payment intent
     * 
     * @urlParam type string required The entity payment is made for. Must be one of participant_registration, participant_transfer, market_resale, charity_membership,partner_package_assignment,event_places,corporate_credit.  Example: participant_registration
     * @param  Request              $request
     * @param  PaymentTypeEnum   $type
     * @return JsonResponse
     */
    public function createPaymentIntent(Request $request, PaymentTypeEnum $type): JsonResponse
    {
        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $paymentIntent = $this->participantRegistrationPayment->createPaymentIntent($request);
                    break;
                case PaymentTypeEnum::ParticipantTransfer:
                    $paymentIntent = $this->participantTransferPayment->createPaymentIntent($request);
                    break;
                default:
                    $paymentIntent = $this->participantRegistrationPayment->createPaymentIntent(new ParticipantRegistrationCreateRequest($request->all()));
            }
        } catch (HttpResponseException $e) {
            throw new HttpResponseException($e->getResponse());
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success('Successfully created the client key!', 200, $paymentIntent);
    }

    /**
     * Checkout - For events that require payment
     * 
     * @urlParam type string required The entity payment is made for. Must be one of participant_registration, participant_transfer, market_resale, charity_membership,partner_package_assignment,event_places,corporate_credit.  Example: participant_registration
     * @urlParam ongoing_external_transaction_ref string required The ref of the ongoing payment. Example: 97ad9df6-d927-4a44-8fec-3daacee89678
     * @param  Request                    $request
     * @param  OngoingExternalTransaction $ongoingExternalTransaction
     * @param  PaymentTypeEnum        $type
     * @return JsonResponse
     */
    public function payCheckout(Request $request, PaymentTypeEnum $type, OngoingExternalTransaction $ongoingExternalTransaction): JsonResponse
    {
        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $result = $this->participantRegistrationPayment->payCheckout($request, $ongoingExternalTransaction);
                    break;
                case PaymentTypeEnum::ParticipantTransfer:
                    $result = $this->participantTransferPayment->payCheckout($request, $ongoingExternalTransaction);
                    break;
                default:
                    $result = $this->participantRegistrationPayment->payCheckout(new ParticipantRegistrationUpdateRequest($request->all()), $ongoingExternalTransaction);
            }
        } catch (HttpResponseException $e) {
            throw new HttpResponseException($e->getResponse());
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success($result['message'], 200, $result);
    }

    /**
     * Checkout - For free events
     * 
     * @urlParam type string required The entity payment is made for. Must be one of participant_registration, participant_transfer, market_resale, charity_membership,partner_package_assignment,event_places,corporate_credit.  Example: participant_registration
     * @param  Request              $request
     * @param  PaymentTypeEnum  $type
     * @return JsonResponse
     */
    public function freeCheckout(Request $request, PaymentTypeEnum $type): JsonResponse
    {
        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $result = $this->participantRegistrationPayment->freeCheckout($request);
                    break;
                default:
                    $result = $this->participantRegistrationPayment->freeCheckout($request);
            }
        } catch (HttpResponseException $e) {
            throw new HttpResponseException($e->getResponse());
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $result['status'] == OngoingExternalTransactionStatusEnum::Failed
            ? $this->error($result['message'], 406, (array) $result)
            : $this->success($result['message'], 200, $result);
    }

    /**
     * Checkout - Confirm payment
     * 
     * @urlParam id string required The id of the payment intent Example: pi_3MtweELkdIwHu7ix0Dt0gF2H
     * @param  string              $id
     * @return JsonResponse
     */
    public function confirm(string $id): JsonResponse
    {
        try {
            $result = $this->participantRegistrationPayment->confirm($id);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success('Payment intent successful!', 200, $result);
    }

    /**
     * Handle payment intent webhook events
     * 
     * @param  Request       $request
     * @return JsonResponse
     */
    public function paymentIntentWebhook(Request $request): JsonResponse
    {
        Log::debug('Payment Intent: ' . clientSite());
        Log::debug('Current Request:' . $this->currentRequest);
        Log::debug($request);

        $type = PaymentTypeEnum::tryFrom($request['data']['object']['metadata']['type']);

        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $result = $this->participantRegistrationPayment->processPaymentIntent($request);
                    break;
                case PaymentTypeEnum::ParticipantTransfer:
                    $result = $this->participantTransferPayment->processPaymentIntent($request);
                    break;
                default:
                    $result = $this->participantRegistrationPayment->processPaymentIntent($request);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success('Payment intent successful!', 200);
    }

    /**
     * Handle payment method webhook events
     * 
     * @param  Request       $request
     * @return JsonResponse
     */
    public function paymentMethodWebhook(Request $request): JsonResponse
    {
        $type = PaymentTypeEnum::tryFrom($request['data']['object']['metadata']['type']);

        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $result = $this->participantRegistrationPayment->processPaymentMethod($request);
                    break;
                case PaymentTypeEnum::ParticipantTransfer:
                    $result = $this->participantTransferPayment->processPaymentMethod($request);
                    break;
                default:
                    $result = $this->participantRegistrationPayment->processPaymentMethod($request);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success('Payment method successful!', 200);
    }

    /**
     * Handle payment link webhook events
     * 
     * @param  Request       $request
     * @return JsonResponse
     */
    public function paymentLinkWebhook(Request $request): JsonResponse
    {
        $type = PaymentTypeEnum::tryFrom($request['data']['object']['metadata']['type']);

        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $result = $this->participantRegistrationPayment->processPaymentLink($request);
                    break;
                case PaymentTypeEnum::ParticipantTransfer:
                    $result = $this->participantTransferPayment->processPaymentLink($request);
                    break;
                default:
                    $result = $this->participantRegistrationPayment->processPaymentLink($request);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }
    
        return $this->success('Payment link successful!', 200);
    }

    /**
     * Handle charge webhook events
     * 
     * @param  Request       $request
     * @return JsonResponse
     */
    public function chargeWebhook(Request $request): JsonResponse
    {
        $type = PaymentTypeEnum::tryFrom($request['data']['object']['metadata']['type']);

        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $result = $this->participantRegistrationPayment->processCharge($request);
                    break;
                case PaymentTypeEnum::ParticipantTransfer:
                    $result = $this->participantTransferPayment->processCharge($request);
                    break;
                default:
                    $result = $this->participantRegistrationPayment->processCharge($request);
            }
        } catch (Exception $e) {
            Log::channel($this->site->code . 'stripecharge')->info("Charge Exception - Unable to process post payment: " . $e->getMessage());
            Log::channel($this->site->code . 'stripecharge')->info($e);

            // $transaction = Transaction::whereHas('ongoingExternalTransaction', function ($query) use ($request) { // Doing this is wrong as it updates for all the  transactions. Handle for each under the catch block of their functions
            //     $query->where('payment_intent_id', $request['data']['object']['payment_intent']);
            // })->update(['status' => TransactionStatusEnum::Failed]);

            return $this->error($e->getMessage(), 406, $e->getMessage());
        }
    
        return $this->success('Charge successful!', 200);
    }

    /**
     * Handle post payment response
     * 
     * @param  Request                     $request
     * @param  OngoingExternalTransaction  $ongoingExternalTransaction
     * @return JsonResponse
     */
    public function postPaymentResponse(Request $request, OngoingExternalTransaction $ongoingExternalTransaction): JsonResponse
    {
        try {
            $result = $this->participantRegistrationPayment->postPaymentResponse($request, $ongoingExternalTransaction);
        } catch (Exception $e) {
            Log::channel($this->site->code . 'stripecharge')->info("Post Payment Response Exception - Unable to process post payment response: " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Post Payment Response Exception - Unable to process post payment response: " . $e->getMessage());
            Log::channel($this->site->code . 'stripecharge')->info($e);
            Log::debug($e);

            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $result->ongoing_external_transaction->status == OngoingExternalTransactionStatusEnum::Failed
            ? $this->error($result->message, 406, (array) $result)
            : $this->success($result->message, 200, $result);
    }

    public function paymentViaWallet(Request $request, PaymentTypeEnum $type, OngoingExternalTransaction $ongoingExternalTransaction): JsonResponse
    {
        try {
            switch ($type) {
                case PaymentTypeEnum::ParticipantRegistration:
                    $result = $this->participantRegistrationPayment->payCheckoutWallet($request, $ongoingExternalTransaction);
                    break;
                default:
                    $result = $this->participantRegistrationPayment->payCheckoutWallet($request, $ongoingExternalTransaction);
            }
        } catch (HttpResponseException $e) {
            throw $e; // No need to wrap it again, just rethrow
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success($result['message'], 200, $result);
    }

    public function postPaymentWalletResponse(Request $request, OngoingExternalTransaction $ongoingExternalTransaction): JsonResponse
    {
        try {
            $result = $this->participantRegistrationPayment->postPaymentWalletResponse($request, $ongoingExternalTransaction);
        } catch (Exception $e) {
            Log::channel($this->site->code . 'wallet')->info("Post Payment Response Exception - Unable to process post payment response: " . $e->getMessage());
            Log::channel($this->site->code . 'adminanddeveloper')->info("Post Payment Response Exception - Unable to process post payment response: " . $e->getMessage());
            Log::channel($this->site->code . 'wallet')->info($e);
            Log::debug($e);

            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $result->ongoing_external_transaction->status == OngoingExternalTransactionStatusEnum::Failed
            ? $this->error($result->message, 406, (array) $result)
            : $this->success($result->message, 200, $result);
    }
}
