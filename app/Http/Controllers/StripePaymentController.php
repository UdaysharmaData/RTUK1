<?php

namespace App\Http\Controllers;

use App\Http\Helpers\AccountType;
use App\Traits\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\RequestException;
use Exception;
use Stripe\Stripe;
use Stripe\PaymentMethod;
use Stripe\Customer;

class StripePaymentController extends Controller
{
    private $stripe;
    use Response;
    public function __construct()
    {
        $apiKey = config('services.stripe.secret');
        if (!$apiKey) {
            abort(500, 'Stripe API key not found');
        }
        $this->stripe = new \Stripe\StripeClient($apiKey);
    }

    private function getAuthenticatedUser()
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }

    private function validateRequest(Request $request, array $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422)->throwResponse();
        }
    }

    public function getUserAllCardDetails()
    {
        if (!AccountType::isParticipant()) {
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            $user = $this->getAuthenticatedUser();
            $customerId = $user->stripe_customer_id;

            if (!$customerId) {
                return response()->json(['error' => 'Stripe customer ID not found'], 400);
            }

            // Make a request to Stripe API to get all card details
            $response = Http::withBasicAuth(config('services.stripe.secret'), '')
                ->get("https://api.stripe.com/v1/customers/{$customerId}/sources", [
                    'object' => 'card'
                ]);

            if ($response->successful()) {
                $cards = $response->json()['data'];
                $uniqueCards = [];
                $fingerprints = [];

                // Filter out duplicate cards based on fingerprint
                foreach ($cards as $card) {
                    if (!in_array($card['fingerprint'], $fingerprints)) {
                        $fingerprints[] = $card['fingerprint'];
                        $uniqueCards[] = $card;
                    }
                }

                return response()->json([
                    'message' => 'Customer Cards Details Fetched Successfully.',
                    'status' => 'success',
                    'data' => $uniqueCards

                ], 200);
            }

            return response()->json(['error' => 'Unable to fetch customer cards', 'details' => $response->body()], $response->status());

        } catch (RequestException $e) {
            return response()->json(['error' => 'HTTP request failed', 'message' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    public function addUserCardDetail(Request $request)
    {
        if (!AccountType::isParticipant()) {
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $this->validateRequest($request, [
            'name' => 'required|string|max:255',
            'token_card' => 'required|string'
        ]);

        try {
            $user = $this->getAuthenticatedUser();

            // Retrieve token card details to get last 4 digits
            $token = $this->stripe->tokens->retrieve($request->token_card);
            $tokenCardLast4 = $token->card->last4;
            $tokenCardBrand = $token->card->brand;

            if ($user->stripe_customer_id) {
                // Check existing cards for duplicates
                $existingCards = $this->stripe->customers->allSources($user->stripe_customer_id, ['object' => 'card']);
                foreach ($existingCards->data as $card) {
                    if ($card->last4 === $tokenCardLast4 && $card->brand === $tokenCardBrand) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Duplicate card detected. The card is already associated with your account.'
                        ], 400);
                    }
                }

                // Add a new card source to an existing customer
                $stripeData = $this->stripe->customers->createSource($user->stripe_customer_id, [
                    'source' => $request->token_card
                ]);

                // Update name on the new card
                $this->stripe->customers->updateSource(
                    $user->stripe_customer_id,
                    $stripeData->id,
                    ['name' => $request->name]
                );

                return response()->json([
                    'message' => 'Customer Card Data Added.',
                    'status' => 'success',
                    'card_data' => $stripeData
                ], 200);
            } else {
                // Create a new Stripe customer and add a card source
                $customer = $this->stripe->customers->create([
                    'email' => $user->email,
                    'name' => $request->name,
                ]);
                $user->update(['stripe_customer_id' => $customer->id]);

                $stripeData = $this->stripe->customers->createSource($customer->id, [
                    'source' => $request->token_card
                ]);

                // Update name on the new card
                $this->stripe->customers->updateSource(
                    $user->stripe_customer_id,
                    $stripeData->id,
                    ['name' => $request->name]
                );

                return response()->json([
                    'message' => 'Stripe Customer Created and Card Attached.',
                    'status' => 'success',
                    'card_data' => $stripeData
                ], 201);
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stripe API error',
                'error' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteUserCardDetails(Request $request)
    {
        if (!AccountType::isParticipant()) {
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $this->validateRequest($request, [
            'card_id' => 'required|string',
        ]);

        try {
            $user = $this->getAuthenticatedUser();
            $cardId = $request->card_id;

            if (strpos($cardId, 'card_') === 0) {
                // If card_id starts with 'card_', delete from customer's sources
                $this->stripe->customers->deleteSource(
                    $user->stripe_customer_id,
                    $cardId
                );
            } elseif (strpos($cardId, 'pm_') === 0) {
                // If card_id starts with 'pm_', detach as a payment method
                $this->stripe->paymentMethods->detach($cardId);
            } else {
                // Handle unrecognized card_id format
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid card_id format'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Card deleted successfully'
            ], 200);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stripe API error',
                'error' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateUserCardDetails(Request $request)
    {
        if (!AccountType::isParticipant()) {
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $this->validateRequest($request, [
            'name' => 'required|string|max:255',
            'exp_month' => 'required|integer|min:1|max:12',
            'exp_year' => 'required|integer|min:' . now()->year,
            'card_id' => 'required|string'
        ]);

        try {
            $user = $this->getAuthenticatedUser();
            $stripeCustomerId = $user->stripe_customer_id;

            // Update the card details
            $updatedCard = $this->stripe->customers->updateSource(
                $stripeCustomerId,
                $request->card_id,
                ['name' => $request->name, 'exp_month' => $request->exp_month, 'exp_year' => $request->exp_year]
            );

            // Update the default source if specified
            if ($request->default_source == 'Yes') {
                $this->stripe->customers->update(
                    $stripeCustomerId,
                    ['default_source' => $request->card_id]
                );
            }

            return response()->json([
                'message' => 'Card updated successfully.',
                'status' => true,
                'code' => 200,
                'data' => $updatedCard
            ], 200);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage()
            ], 400);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }

    public function getCustomerPaymentMethod()
    {
        // Ensure the user has the appropriate account type
        if (!AccountType::isParticipant()) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to access this resource!'], 403);
        }

        try {
            $user = auth()->user();
            $stripeCustomerId = $user->stripe_customer_id;

            if (!$stripeCustomerId) {
                return response()->json(['success' => false, 'message' => 'Stripe customer ID not found for this user.'], 404);
            }

            Stripe::setApiKey(config('services.stripe.secret'));

            // Fetch the customer object to get the default_source
            $customer = Customer::retrieve($stripeCustomerId);
            $defaultSource = $customer->invoice_settings->default_payment_method;

            // Fetch the payment methods
            $paymentMethods = PaymentMethod::all([
                'customer' => $stripeCustomerId,
                'type' => 'card',
            ]);

            $paymentMethodsArray = $paymentMethods->data;

            // Check which payment method is the default
            foreach ($paymentMethodsArray as &$paymentMethod) {
                $paymentMethod->is_default = ($paymentMethod->id === $defaultSource);
            }

            return response()->json([
                'message' => 'Payment Details Fetched Successfully.',
                'status' => 'success',
                'data' => $paymentMethodsArray,
            ], 200);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['success' => false, 'message' => 'Failed to fetch payment methods: ' . $e->getMessage()], 500);
        }
    }

    public function changeDefaultPaymentMethod(Request $request)
    {
        // Ensure the user has the appropriate account type
        if (!AccountType::isParticipant()) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to access this resource!'], 403);
        }

        try {
            $user = auth()->user();
            $stripeCustomerId = $user->stripe_customer_id;
            $newDefaultPaymentMethodId = $request->input('payment_method_id');

            if (!$stripeCustomerId) {
                return response()->json(['success' => false, 'message' => 'Stripe customer ID not found for this user.'], 404);
            }

            if (!$newDefaultPaymentMethodId) {
                return response()->json(['success' => false, 'message' => 'Payment method ID is required.'], 400);
            }

            Stripe::setApiKey(config('services.stripe.secret'));

            // Fetch the payment methods to ensure the provided payment method ID belongs to the user
            $paymentMethods = PaymentMethod::all([
                'customer' => $stripeCustomerId,
                'type' => 'card',
            ]);

            $paymentMethodsArray = $paymentMethods->data;
            $isValidPaymentMethod = false;

            foreach ($paymentMethodsArray as $paymentMethod) {
                if ($paymentMethod->id === $newDefaultPaymentMethodId) {
                    $isValidPaymentMethod = true;
                    break;
                }
            }

            if (!$isValidPaymentMethod) {
                return response()->json(['success' => false, 'message' => 'Invalid payment method ID.'], 400);
            }

            // Update the customer's default payment method in Stripe
            $customer = Customer::update($stripeCustomerId, [
                'invoice_settings' => ['default_payment_method' => $newDefaultPaymentMethodId],
            ]);

            return response()->json(['success' => true, 'message' => 'Default payment method updated successfully.'], 200);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['success' => false, 'message' => 'Failed to change default payment method: ' . $e->getMessage()], 500);
        }
    }

}
