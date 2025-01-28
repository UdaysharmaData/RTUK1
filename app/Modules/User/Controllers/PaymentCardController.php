<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentCard;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\StorePaymentCardRequest;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentCardController extends Controller
{
    use Response;
    /**
     * User Payment Cards
     *
     * Payment cards added to user's account user.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cards(): JsonResponse
    {
        return $this->success('User Payment Cards', 200, [
            'cards' => request()->user()->paymentCards
        ]);
    }

    /**
     * Add Payment Card
     *
     * Add a payment card to user account.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam card_name string required The official name on the card. Example: Sara Lulu
     * @bodyParam card_number string required The 16 digits number on card. Example: 1234123412341234
     * @bodyParam expiry_date date required The expiry date on card (include a default 01 as day). Example: 01/12/2026
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param StorePaymentCardRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(StorePaymentCardRequest $request, User $user): JsonResponse
    {
        try {
            $card = $user->paymentCards()->create($request->validated());

            return $this->success('Payment Card added.', 200, [
                'cards' => $card
            ]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->error('An error occurred while trying to store card details.', 400);
        }
    }

    /**
     * Remove Payment Card
     *
     * Delete a payment card from user account.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam card string required Specifies card's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param PaymentCard $card
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function remove(PaymentCard $card): JsonResponse
    {
        abort_unless(
            (request()->user()->id === $card->user_id || request()->user()->isAdmin()),
            403,
            'You are not authorized to delete this card.'
        );

        try {
            $card->delete();

            return $this->success('Payment Card removed.');
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->error('An error occurred while trying to remove card.', 400);
        }
    }
}
