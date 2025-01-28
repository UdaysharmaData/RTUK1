<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;
use App\Http\Controllers\PaymentController;
use App\Services\PasswordProtectionPolicy\Middleware\MaximumPasswordAgePolicy;

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
Route::prefix('v1')->group(function () {
    Route::get('/validate-path/{path}/fetch', [App\Http\Controllers\EnquiryController::class, 'validatePath'])->where('path', '.*');
    Route::middleware(['user.status.active'])->group(function () {
    //    Route::get('/ping', App\Http\Controllers\ApiRequestAuthorization::class);
        // Generate Identifier
        Route::get('/get-identifier-token', App\Http\Controllers\GenerateApiPlatformUserIdentifier::class);

        Route::get('/stats', \App\Http\Controllers\StatsController::class);

        Route::post('/auth/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login')->middleware('verify.recaptcha');
        Route::post('/auth/register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register')->middleware('verify.recaptcha');
        Route::post('/socials/validate', \App\Http\Controllers\ConfirmSocialActionController::class);
        Route::post('/auth/{user:ref}/login_via_website', [App\Http\Controllers\Auth\LoginController::class, 'login_via_website'])->name('login_via_website');

        // Password setup
        Route::post('account/password/create', [App\Http\Controllers\Auth\PasswordSetupController::class, 'setup'])->name('password.setup.confirm')->middleware('verify.recaptcha');
        Route::post('account/password/resend-setup-code', [App\Http\Controllers\Auth\PasswordSetupController::class, 'resendSetupCode'])->name('password.setup.resend')->middleware('verify.recaptcha');

        // Password reset
        Route::post('password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('verify.recaptcha');
        Route::post('password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update')->middleware('verify.recaptcha');

        Route::middleware([
            'auth:api',
    //        MaximumPasswordAgePolicy::class
        ])->group(function () {
            Route::get('/users/current', [\App\Modules\User\Controllers\UserController::class, 'current']);
            Route::post('/auth/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
            Route::delete('/users/{user:ref}/remove-devices', \App\Modules\User\Controllers\Actions\DisconnectDevices::class);

            // Two-factor authentication
            Route::prefix('2fa')->group(function () {
                Route::get('', [\App\Modules\User\Controllers\TwoFactorAuthController::class, 'index']);
                Route::post('/{method:ref}/enable', [\App\Modules\User\Controllers\TwoFactorAuthController::class, 'enableTwoFactorAuth']);
                Route::post('/{method:ref}/disable', [\App\Modules\User\Controllers\TwoFactorAuthController::class, 'disableTwoFactorAuth']);
                Route::post('/{method:ref}/default', [\App\Modules\User\Controllers\TwoFactorAuthController::class, 'markAsDefault']);
                Route::post('/{method:ref}/otp/send', [\App\Modules\User\Controllers\TwoFactorAuthController::class, 'sentOtpCode']);
                Route::post('/token/generate', [\App\Modules\User\Controllers\TwoFactorAuthController::class, 'generateTwoFactorToken']);
                Route::post('/token/valid', [\App\Modules\User\Controllers\TwoFactorAuthController::class, 'twoFactorTokenValidity'])
                    ->middleware('verified.2fa');
                Route::post('/recovery-codes/generate', [\App\Modules\User\Controllers\TwoFactorAuthController::class, 'generateRecoveryCodes'])
                    ->middleware('verified.2fa');
            });

            // User notifications
            Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
            Route::get('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'read']);

            Route::get('profile/{profile:ref}/view', [App\Modules\User\Controllers\ProfileController::class, 'view'])->middleware('can:view,profile');

            Route::middleware(['verified.email'])->group(function() {
                Route::patch('profile/{profile:ref}/update', [App\Modules\User\Controllers\ProfileController::class, 'update'])->middleware('can:update,profile');
                Route::post('/profile/{profile}/avatar-upload', \App\Modules\User\Controllers\Actions\ProfileAvatarUpload::class);
                Route::post('/profile/{profile}/background-image-upload', \App\Modules\User\Controllers\Actions\ProfileBackgroundImageUpload::class);
                Route::get('/profile/{profile:ref}/avatar-delete', \App\Modules\User\Controllers\Actions\ProfileAvatarDelete::class)->middleware('can:delete-avatar,profile');
                Route::get('/profile/{profile:ref}/background-image-delete', \App\Modules\User\Controllers\Actions\ProfileBackgroundImageDelete::class)->middleware('can:delete-background-image,profile');
                Route::patch('/users/{user:ref}/update-password', \App\Modules\User\Controllers\Actions\UpdateUserPassword::class)->middleware(['can:update-password,user', 'verified.2fa']);
            });

            // Account verification
            Route::post('account/verify', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])->name('verification.verify')->middleware('verify.recaptcha');
            Route::post('account/verification/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])->name('verification.resend')->middleware('verify.recaptcha');

            Route::post('passphrase/verify', [App\Services\PasswordProtectionPolicy\Controllers\PassphraseController::class, 'verify'])->name('passphrase.verify');
            Route::post('passphrase/store', [App\Services\PasswordProtectionPolicy\Controllers\PassphraseController::class, 'store'])->name('passphrase.store');

            // passphrase.verify middleware
            Route::middleware(['passphrase.verify', MaximumPasswordAgePolicy::class])->group(function () {
                Route::patch('passphrase/update', [App\Services\PasswordProtectionPolicy\Controllers\PassphraseController::class, 'update'])->name('passphrase.update');
            });

    //        Route::post('upload/{uploader}', [App\Services\FileManager\Controllers\FileManagerController::class, 'upload'])->name('upload');

            Route::get('roles', [App\Modules\User\Controllers\RoleController::class, 'index']);
            Route::get('roles/{ref}/show', [App\Modules\User\Controllers\RoleController::class, 'show']);
            Route::get('roles/options', [App\Modules\User\Controllers\RoleController::class, 'create']);

            Route::middleware(['verified.email'])->group(function () {
                Route::post('roles/store', [App\Modules\User\Controllers\RoleController::class, 'store']);
                Route::patch('roles/{role:ref}/update', [App\Modules\User\Controllers\RoleController::class, 'update']);
                Route::delete('role/delete', [App\Modules\User\Controllers\RoleController::class, 'delete']);
                Route::patch('role/restore', [App\Modules\User\Controllers\RoleController::class, 'restore']);
            });

            Route::get('/enquiries', [App\Http\Controllers\EnquiryController::class, 'index']);
            Route::get('/enquiries/{enquiry:ref}', [App\Http\Controllers\EnquiryController::class, 'show']);

            Route::get('/faq-categories', [App\Http\Controllers\FaqCategoryController::class, 'index']);
            Route::get('/faq-categories/types', [App\Http\Controllers\FaqCategoryController::class, 'create']);
            Route::get('/faq-categories/{category:ref}', [App\Http\Controllers\FaqCategoryController::class, 'show']);
            Route::middleware(['verified.email'])->group(function () {
                Route::post('/faq-categories/{category:ref}/store', [App\Http\Controllers\FaqCategoryController::class, 'store']);
                Route::patch('/faq-categories/{category:ref}/update', [App\Http\Controllers\FaqCategoryController::class, 'update']);
                Route::delete('/faq-categories/{category:ref}/delete', [App\Http\Controllers\FaqCategoryController::class, 'destroy']);
            });

            Route::get('/media-libraries', [App\Http\Controllers\MediaLibraryController::class, 'index']);
            Route::get('/media-libraries/{media-library:ref}', [App\Http\Controllers\MediaLibraryController::class, 'show']);
            Route::middleware(['verified.email'])->group(function () {
                Route::post('/media-libraries/store', [App\Http\Controllers\MediaLibraryController::class, 'store']);
                Route::patch('/media-libraries/{media-library:ref}/update', [App\Http\Controllers\MediaLibraryController::class, 'update']);
                Route::delete('/media-libraries/{media-library:ref}/delete', [App\Http\Controllers\MediaLibraryController::class, 'destroy']);
            });

            Route::get('/media-collections', [App\Http\Controllers\MediaLibraryCollectionController::class, 'index']);
            Route::get('/media-collections/{media-collection:ref}', [App\Http\Controllers\MediaLibraryCollectionController::class, 'show']);
            Route::middleware(['verified.email'])->group(function () {
                Route::post('/media-collections/store', [App\Http\Controllers\MediaLibraryCollectionController::class, 'store']);
                Route::patch('/media-collections/{media-collection:ref}/update', [App\Http\Controllers\MediaLibraryCollectionController::class, 'update']);
                Route::delete('/media-collections/{media-collection:ref}/delete', [App\Http\Controllers\MediaLibraryCollectionController::class, 'destroy']);
            });

            Route::get('/api-clients', [App\Http\Controllers\ApiClientController::class, 'index']);
            Route::get('/api-clients/{api-client:ref}', [App\Http\Controllers\ApiClientController::class, 'show']);
            Route::middleware(['verified.email'])->group(function () {
                Route::post('/api-clients/store', [App\Http\Controllers\ApiClientController::class, 'store']);
                Route::patch('/api-clients/{api-client:ref}/update', [App\Http\Controllers\ApiClientController::class, 'update']);
                Route::delete('/api-clients/{enquiry:ref}/delete', [App\Http\Controllers\ApiClientController::class, 'destroy']);
            });

            Route::patch('/uploads/{upload:ref}/update', [App\Http\Controllers\FileController::class, 'updateInfo'])->middleware('verified.email');
        });

        Route::get('download/{path}', [FileController::class, 'download'])->where('path', '.*');

        Route::get('testing', function() {
            // $result = App\Modules\Charity\Models\CharityListing::with(['charityCharityListings', 'listingPage.event'])->oldest()->first();
            // $result->two_year_charities = $result->twoYearCharities();
            // $result->partner_charities = $result->partnerCharities();
            // $result->secondary_charities = $result->secondaryCharities();

            // foreach ($result->partnerCharities as $key => $partnerCharity) {
            //     // $partnerCharity->load('listingPage.event.eventPages');
            //     // $result->partnerCharities[$key]->charityListing->listingPage->event->event_page = \App\Modules\Event\Models\EventPage::where('charity_id', $partnerCharity->charity_id)->where('event_id', $partnerCharity->charityListing->listingPage->event->id)->first();
            //     $result->partnerCharities[$key]->event_page = \App\Modules\Event\Models\EventPage::where('id', $partnerCharity->charity_id)->where('event_id', $partnerCharity->charityListing->listingPage->event->id)->first();
            // }
            // return $result;
            return [];
        });

        // Interactions
        Route::get('/analytics/pages/{page:ref}/interact', App\Http\Controllers\Analytics\Interactions\PageInteractionController::class);
        Route::get('/analytics/events/{event:ref}/interact', App\Http\Controllers\Analytics\Interactions\EventInteractionController::class);

        // Views
        Route::get('/analytics/pages/{page:ref}/view', App\Http\Controllers\Analytics\Views\PageViewController::class);
        Route::get('/analytics/events/{event:ref}/view', App\Http\Controllers\Analytics\Views\EventViewController::class);
    });
});
