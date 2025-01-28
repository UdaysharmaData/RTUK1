<?php

namespace App\Modules\Finance\Controllers;

use Log;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;

use App\Modules\Finance\Enums\TransactionPaymentMethodEnum;

use App\Modules\Finance\Requests\PaymentMethodCreateRequest;

use App\Modules\User\Models\User;

/**
 * @group Payment
 * Handles all payment methods related operations
 * @authenticated
 */
class PaymentMethodController extends PaymentController
{
    use Response,
        SiteTrait,
        UploadTrait,
        DownloadTrait,
        SingularOrPluralTrait;

    /*
    |--------------------------------------------------------------------------
    | Payment Method Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with payment methods. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create and attach payment method to customer
     * 
     * @urlParam id string required The payment method id. Example: pm_1MqLiJLkdIwHu7ixUEgbFdYF
     * @param  PaymentMethodCreateRequest  $request
     * @return JsonResponse
     */
    public function create(PaymentMethodCreateRequest $request): JsonResponse
    {
        $user = Auth::user();
        $exists = true; // Assume customer exists on stripe
        $data = [];

        try {
            switch ($request->type) {
                case TransactionPaymentMethodEnum::Card->value:
                    $data = [
                        TransactionPaymentMethodEnum::Card->value => [
                            'number' => $request->number,
                            'exp_month' => $request->exp_month,
                            'exp_year' => $request->exp_year,
                            'cvc' => $request->cvc
                        ]
                    ];
                break;
                case TransactionPaymentMethodEnum::BacsDebit->value:
                    $data = [
                        TransactionPaymentMethodEnum::BacsDebit->value => [
                            'account_number' => $request->account_number,
                            'sort_code' => $request->sort_code
                        ]
                    ];
                break;
                case TransactionPaymentMethodEnum::Paypal:
                case TransactionPaymentMethodEnum::Link:
                break;

                default;
            }

            $paymentMethod = $this->stripe->paymentMethods->create([ // Create the payment method
                'type' => $request->type,
                ...$data
            ]);

            if ($user->stripe_customer_id) {
                $exists = $this->stripe->customers->retrieve($user->stripe_customer_id, []); // Ensure the customer exists

                if ($exists) { // Attach payment method to customer
                    $paymentMethod = $this->stripe->paymentMethods->attach($paymentMethod->id, [ // Attach the payment method
                        'customer' => $user->stripe_customer_id
                    ]);
                } else {
                    $exists = false;
                }
            }

            if (!$user->stripe_customer_id || !$exists) { // Create the customer and attach payment method to them
                $customer = $this->stripe->customers->create([
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'payment_method' => $paymentMethod->id
                ]);

                Log::channel($this->site->code . 'stripepaymentintent')->info("$user->email is a new customer");
                $user->update(['stripe_customer_id' => $customer->id]);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406);
        }

        return $this->success('Successfully attached payment method!', 200, $paymentMethod);
    }

    /**
     * Attach payment method to customer
     * 
     * @urlParam id string required The payment method id. Example: pm_1MqLiJLkdIwHu7ixUEgbFdYF
     * @param  string              $id
     * @return JsonResponse
     */
    public function attach(string $id): JsonResponse
    {
        $user = Auth::user();

        try {
            if ($user->stripe_customer_id) {
                $paymentMethod = $this->stripe->customers->retrievePaymentMethod( // Retrieve the customer's payment method while ensuring it belongs to the user
                    $user->stripe_customer_id,
                    $id
                );

                if ($paymentMethod) {
                    $paymentMethod = $this->stripe->paymentMethods->attach($paymentMethod->id, [ // Attach the payment method
                        'customer' => $user->stripe_customer_id
                    ]);
                } else {
                    throw new Exception('The payment method was not found!');
                }
            } else {
                throw new Exception('The user is not a customer on stripe!');
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406);
        }

        return $this->success('Successfully attached payment method!', 200, $paymentMethod);
    }

    /**
     * Detach payment method from customer
     * 
     * @urlParam id string required The payment method id. Example: pm_1MqLiJLkdIwHu7ixUEgbFdYF
     * @param  string              $id
     * @return JsonResponse
     */
    public function detach(string $id): JsonResponse
    {
        $user = Auth::user();

        try {
            if ($user->stripe_customer_id) {
                $paymentMethod = $this->stripe->customers->retrievePaymentMethod( // Retrieve the customer's payment method while ensuring it belongs to the user
                    $user->stripe_customer_id,
                    $id
                );

                if ($paymentMethod) {
                    $paymentMethod = $this->stripe->paymentMethods->detach($paymentMethod->id); // Detach the payment method
                } else {
                    throw new Exception('The payment method was not found!');
                }
            } else {
                throw new Exception('The user is not a customer on stripe!');
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406);
        }

        return $this->success('Successfully detached payment method!', 200, $paymentMethod);
    }

    /**
     * List a customer's payment methods
     * 
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $user = Auth::user();

        try {
            if ($user->stripe_customer_id) {
                    $paymentMethods = $this->stripe->customers->allPaymentMethods($user->stripe_customer_id); // Get customer's payment methods
            } else {
                throw new Exception('The user is not a customer on stripe!');
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 406);
        }

        return $this->success('The list of payment methods!', 200, $paymentMethods);
    }
}
