<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Controllers\PaymentController;
use App\Modules\Finance\Controllers\PaymentMethodController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('intent')->middleware('client.valid')->group(function () {
    // Route::post('create', [PaymentController::class, 'createPaymentIntent']);
    // Route::post('{ongoing_external_transaction:ref}/update', [PaymentController::class, 'updatePaymentIntent']);

    // Route::post('confirm', [PaymentController::class, 'confirmPaymentIntent']);
    // Route::post('cancel', [PaymentController::class, 'cancelPaymentIntent']);
    // Route::post('retrieve', [PaymentController::class, 'retrievePaymentIntent']);
});

Route::group(['prefix' => 'webhook', 'middleware' => ['client.valid']], function () {
    Route::post('intent', [PaymentController::class, 'paymentIntentWebhook']);
    Route::post('link', [PaymentController::class, 'paymentLinkWebhook']);
    Route::post('charge', [PaymentController::class, 'chargeWebhook']);
});

Route::post('webhook/method', [PaymentController::class, 'paymentMethodWebhook']);

Route::prefix('checkout')->middleware('client.valid')->group(function () {
    Route::get('meta', [PaymentController::class, 'metaData']);
    Route::post('{type}/proceed', [PaymentController::class, 'proceedToCheckout']);
    Route::post('{type}/{ongoing_external_transaction:ref}/pay', [PaymentController::class, 'payCheckout']);
    Route::post('{type}/free', [PaymentController::class, 'freeCheckout']);
    Route::post('{id}/confirm', [PaymentController::class, 'confirm']);
    // Route::post('{ongoing_external_transaction:ref}/response', [PaymentController::class, 'postPaymentResponse'])->withTrashed();
    Route::get('{ongoing_external_transaction:ref}/response', [PaymentController::class, 'postPaymentResponse'])->withTrashed();

    Route::post('{type}/{ongoing_external_transaction:ref}/paymentViaWallet', [PaymentController::class, 'paymentViaWallet']);
    Route::get('{ongoing_external_transaction:ref}/postPaymentWalletResponse', [PaymentController::class, 'postPaymentWalletResponse'])->withTrashed();
});

Route::prefix('payment-methods')->middleware(['auth:api', 'client.valid'])->group(function () {
    Route::post('create', [PaymentMethodController::class, 'create']);
    Route::post('{id}/attach', [PaymentMethodController::class, 'attach']);
    Route::post('{id}/detach', [PaymentMethodController::class, 'detach']);
    Route::post('{id}/list', [PaymentMethodController::class, 'list']);
});