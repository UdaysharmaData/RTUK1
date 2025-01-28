<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use \App\Http\Controllers\CityController;
use \App\Http\Controllers\VenueController;
use \App\Http\Controllers\MedalController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\Portal\DripController;
use App\Http\Controllers\Portal\RoleController;
use App\Http\Controllers\Portal\SiteController;
use \App\Modules\User\Controllers\UserController;
use App\Http\Controllers\Portal\EventController;
use App\Http\Controllers\Portal\EntryController;
use App\Http\Controllers\Portal\RegionController;
use App\Http\Controllers\Portal\MarketController;
use App\Http\Controllers\Portal\CharityController;
use App\Http\Controllers\Portal\GeneralController;
use App\Http\Controllers\Portal\InvoiceController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\SettingController;
use App\Modules\Event\Controllers\SerieController;
use App\Http\Controllers\Portal\PermissionController;
use App\Modules\Event\Controllers\SponsorController;
use App\Modules\Finance\Controllers\FinanceController;
use App\Modules\Enquiry\Controllers\EnquiryController;
use App\Modules\Event\Controllers\BookEventController;
use App\Http\Controllers\Portal\ResalePlaceController;
use App\Modules\Partner\Controllers\PartnerController;
use App\Modules\Event\Controllers\PartnerEventController;
use App\Http\Controllers\Portal\CharitySignupController;
use App\Http\Controllers\Portal\EventCategoryController;
use App\Http\Controllers\Portal\CharityCategoryController;
use App\Modules\Partner\Controllers\PartnerChannelController;
use App\Modules\Participant\Controllers\ParticipantController;
use App\Modules\Enquiry\Controllers\ExternalEnquiryController;
use App\Services\Reporting\Controllers\CityStatisticsController;
use App\Services\Reporting\Controllers\VenueStatisticsController;
use App\Services\Reporting\Controllers\EntryStatisticsController;
use App\Services\Reporting\Controllers\EventStatisticsController;
use App\Services\Reporting\Controllers\RegionStatisticsController;
use App\Services\Reporting\Controllers\EnquiryStatisticsController;
use App\Services\Reporting\Controllers\InvoiceStatisticsController;
use App\Services\Reporting\Controllers\PartnerStatisticsController;
use App\Services\Reporting\Controllers\ExperienceStatisticsController;
use App\Services\Reporting\Controllers\ParticipantStatisticsController;
use App\Services\Reporting\Controllers\EventCategoryStatisticsController;
use App\Services\Reporting\Controllers\ExternalEnquiryStatisticsController;
use App\Services\Reporting\Controllers\MedalStatisticsController;
use App\Services\Reporting\Controllers\SerieStatisticsController;
use App\Services\Reporting\Controllers\SponsorStatisticsController;

/*
|--------------------------------------------------------------------------
| Portal Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Portal routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Don't require authentication
Route::group([], function () {

});

// Requires authentication
Route::group(['middleware' => [
    'user.status.active',
    'auth:api',
//    'verified.email'
    ]], function () {
    // Sites
    Route::group(['prefix' => 'sites'], function () {
        Route::get('all', [SiteController::class, 'all']);
        Route::get('/', [SiteController::class, 'index']);
        Route::get('create', [SiteController::class, 'create']);
        Route::post('create', [SiteController::class, 'store'])->middleware('verified.email');
        Route::get('{id}/details', [SiteController::class, 'show']);
        Route::get('{id}/edit', [SiteController::class, 'edit']);
        Route::put('{id}/update', [SiteController::class, 'update'])->middleware('verified.email');
        Route::delete('{id}/delete', [SiteController::class, 'destroy'])->middleware('verified.email');
    });

//    Route::post('users/create', [UserController::class, 'create']);
//    Route::get('users/{email}/details', [UserController::class, 'profile']);
//    Route::put('users/{id}/update', [UserController::class, 'update']);
//    Route::delete('users/{email}/delete', [UserController::class, 'delete']);
//    Route::post('users/password/change', [UserController::class, 'changePassword']);
//    Route::post('users/{id}/login', [UserController::class, 'loginAsAnotherUser']);

    Route::get('/users/{user}/settings', [UserController::class, 'settings']);
    Route::patch('/users/{user:ref}/update-personal-info', \App\Modules\User\Controllers\Actions\UpdateUserPersonalInfo::class);

    Route::middleware(['participant'])->group(function () {
        Route::put('/users/{user:ref}/update-socials', \App\Modules\User\Controllers\Actions\UpdateUserSocials::class);
        Route::delete('/users/{user:ref}/delete-socials', \App\Modules\User\Controllers\Actions\DeleteUserSocials::class);
    });

    Route::patch('/users/{user:ref}/switch-active-role', \App\Modules\User\Controllers\Actions\SwitchActiveRole::class);

    // Cards
    Route::get('/users/cards', [\App\Modules\User\Controllers\PaymentCardController::class, 'cards']);
    Route::post('/users/{user:ref}/add-card', [\App\Modules\User\Controllers\PaymentCardController::class, 'add']);
    Route::delete('/cards/{card:ref}/remove', [\App\Modules\User\Controllers\PaymentCardController::class, 'remove']);

    // Dashboard Stats
    Route::get('/dashboard/stats/summary', [App\Services\Reporting\Controllers\DashboardStatisticsController::class, 'summary']);
    Route::get('/dashboard/stats/chart', [App\Services\Reporting\Controllers\DashboardStatisticsController::class, 'chart']);

    Route::get('/dashboard/stats/netRevenue', [App\Services\Reporting\Controllers\DashboardStatisticsController::class, 'netRevenue']);
    Route::get('/dashboard/stats/eventDataSummary', [App\Services\Reporting\Controllers\DashboardStatisticsController::class, 'eventDataSummary']);
    Route::get('/dashboard/stats/netRevenueEventSummary', [App\Services\Reporting\Controllers\DashboardStatisticsController::class, 'netRevenueEventSummary']);
    Route::get('/dashboard/stats/entriesSummary', [App\Services\Reporting\Controllers\DashboardStatisticsController::class, 'entriesSummary']);
    Route::get('/dashboard/stats/participantsSummary', [App\Services\Reporting\Controllers\DashboardStatisticsController::class, 'participantsSummary']);


    Route::middleware(['admin'])->group(function () {
        // Uploads
        Route::get('uploads', [App\Http\Controllers\UploadController::class, 'index']);
        Route::get('uploads/create', [App\Http\Controllers\UploadController::class, 'create']);
        Route::get('uploads/{ref}/show', [App\Http\Controllers\UploadController::class, 'show']);
        Route::post('uploads/store', [App\Http\Controllers\UploadController::class, 'store']);
        Route::post('uploads/{upload:ref}/update', [App\Http\Controllers\UploadController::class, 'update']);
        Route::delete('/uploads/{upload:ref}/delete', [App\Http\Controllers\UploadController::class, 'destroy']);
        Route::delete('/uploads/delete-many', [App\Http\Controllers\UploadController::class, 'destroyMany']);
        Route::get('image/version/storage-link', [\App\Http\Controllers\UploadController::class, 'getImageVersionStorageLink']);
        Route::get('uploads/getUploadPdf', [App\Http\Controllers\UploadController::class, 'getUploadPdf']);
        Route::get('uploads/getRaceInfo', [App\Http\Controllers\UploadController::class, 'getRaceInfo']);
        Route::get('uploads/deleteRaceInfo', [App\Http\Controllers\UploadController::class, 'deleteRaceInfo']);
        Route::post('uploads/raceInfoAdd', [App\Http\Controllers\UploadController::class, 'raceInfoAdd']);

        // Event Faqs
        Route::get('/events/{event:ref}/faqs', [App\Http\Controllers\EventFaqController::class, 'index']);
        Route::post('/events/{event:ref}/store-faqs', [App\Http\Controllers\EventFaqController::class, 'store']);
        Route::patch('/events/{event:ref}/update-faq', [App\Http\Controllers\EventFaqController::class, 'update']);
        Route::delete('/events/{event:ref}/delete-faq', [App\Http\Controllers\EventFaqController::class, 'delete']);

        // Pages
        Route::get('/pages', [App\Http\Controllers\PageController::class, 'index']);
        Route::get('/pages/create', [App\Http\Controllers\PageController::class, 'create']);
        Route::get('/pages/edit', [App\Http\Controllers\PageController::class, 'edit']);
        Route::get('/pages/{ref}/show', [App\Http\Controllers\PageController::class, 'show']);
        Route::get('/pages/{ref}/edit', [App\Http\Controllers\PageController::class, 'show']);
        Route::post('/pages/store', [App\Http\Controllers\PageController::class, 'store'])->middleware('verified.email');

        // Customize Pages
        Route::get('/customizePages', [App\Http\Controllers\PageController::class, 'customizePages']);
        Route::get('/customizePages/customizePagesAdd', [App\Http\Controllers\PageController::class, 'customizePagesAdd']);
        Route::get('/customizePages/customizePagesEdit', [App\Http\Controllers\PageController::class, 'customizePagesEdit']);
        Route::get('/customizePages/{ref}/customizePagesShow', [App\Http\Controllers\PageController::class, 'customizePagesShow']);
        Route::get('/customizePages/{ref}/customizePagesEdit', [App\Http\Controllers\PageController::class, 'customizePagesShow']);
        Route::put('/customizePages/{customize_pages:ref}/customizePagesUpdate', [App\Http\Controllers\PageController::class, 'customizePagesUpdate']);
        Route::post('/customizePages/customizePagesStore', [App\Http\Controllers\PageController::class, 'customizePagesStore'])->middleware('verified.email');
        Route::post('/customizePages/customizePagesDestroy', [App\Http\Controllers\PageController::class, 'customizePagesDestroy']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('/pages/{page:ref}/update', [App\Http\Controllers\PageController::class, 'update']);
            Route::post('/pages/publish', [App\Http\Controllers\PageController::class, 'markAsPublished']);
            Route::post('/pages/draft', [App\Http\Controllers\PageController::class, 'markAsDraft']);
            Route::delete('/pages/{page:ref}/delete', [App\Http\Controllers\PageController::class, 'destroy']);
            Route::delete('/pages/delete-many', [App\Http\Controllers\PageController::class, 'destroyMany']);
            Route::post('/pages/restore-many', [App\Http\Controllers\PageController::class, 'restoreMany']);
            Route::delete('/pages/{page:ref}/delete-faqs', [App\Http\Controllers\PageController::class, 'destroyManyFaqs']);
            Route::delete('/pages/{page:ref}/{faq:ref}/delete-faq-details', [App\Http\Controllers\PageController::class, 'destroyManyFaqDetails']);
            Route::delete('/pages/{page:ref}/{faq:ref}/{faq_details:ref}/faq-details-image/{upload_ref}/delete', [App\Http\Controllers\PageController::class, 'removeFaqDetailImage']);
            Route::delete('/pages/{page:ref}/delete-meta', [App\Http\Controllers\PageController::class, 'destroyMeta']);

            // Audiences
            Route::get('/audiences', [App\Http\Controllers\AudienceController::class, 'index']);
            Route::get('/audiences/create', [App\Http\Controllers\AudienceController::class, 'create']);
            Route::get('/audiences/{ref}/edit', [App\Http\Controllers\AudienceController::class, 'edit']);
            Route::post('/audiences/store', [App\Http\Controllers\AudienceController::class, 'store']);
            Route::patch('/audiences/{audience:ref}/update', [App\Http\Controllers\AudienceController::class, 'update']);
            Route::delete('/audiences/delete', [App\Http\Controllers\AudienceController::class, 'destroy']);
            Route::post('/audiences/restore', [App\Http\Controllers\AudienceController::class, 'restore']);

            // Audiences mailing lists mgt
            Route::patch('/audiences/{audience:ref}/mailing-list/{mailing_list:ref}/update', [App\Http\Controllers\AudienceController::class, 'updateMailingList']);
            Route::delete('/audiences/{audience:ref}/mailing-list/delete', [App\Http\Controllers\AudienceController::class, 'destroyMailingLists']);
            Route::post('/audiences/{audience:ref}/mailing-list/restore', [App\Http\Controllers\AudienceController::class, 'restoreMailingLists']);

            // Audiences Stats
            Route::get('/audiences/stats/summary', [App\Services\Reporting\Controllers\AudienceStatisticsController::class, 'summary']);
        });

        // Combinations
        Route::get('/combinations', [App\Http\Controllers\CombinationController::class, 'index'])->name('portal.combinations.index');
        Route::get('/combinations/create', [App\Http\Controllers\CombinationController::class, 'create']);
        Route::get('/combinations/{combination}/edit', [App\Http\Controllers\CombinationController::class, 'show']);
        Route::post('/combinations/store', [App\Http\Controllers\CombinationController::class, 'store'])->middleware('verified.email');
        
        Route::get('/combinations/getCitiesForCombination', [App\Http\Controllers\CombinationController::class, 'getCitiesForCombination']);
        Route::get('/combinations/getVanuesForCombination', [App\Http\Controllers\CombinationController::class, 'getVanuesForCombination']);
        Route::get('/combinations/getSeriesForCombination', [App\Http\Controllers\CombinationController::class, 'getSeriesForCombination']);
        Route::get('/combinations/getYearsForCombination', [App\Http\Controllers\CombinationController::class, 'getYearsForCombination']);
        Route::get('/combinations/getMonthForCombination', [App\Http\Controllers\CombinationController::class, 'getMonthForCombination']);
        
        Route::middleware(['verified.email'])->group(function () {
            Route::patch('/combinations/{combination:ref}/update', [App\Http\Controllers\CombinationController::class, 'update']);
            Route::post('/combinations/publish', [App\Http\Controllers\CombinationController::class, 'markAsPublished']);
            Route::post('/combinations/draft', [App\Http\Controllers\CombinationController::class, 'markAsDraft']);
            Route::delete('/combinations/delete-many', [App\Http\Controllers\CombinationController::class, 'destroyMany']);
            Route::post('/combinations/restore-many', [App\Http\Controllers\CombinationController::class, 'restoreMany']);
            Route::delete('/combinations/{combination:ref}/delete-faqs', [App\Http\Controllers\CombinationController::class, 'destroyManyFaqs']);
            Route::delete('/combinations/{combination:ref}/{faq:ref}/delete-faq-details', [App\Http\Controllers\CombinationController::class, 'destroyManyFaqDetails']);
            Route::delete('/combinations/{combination:ref}/{faq:ref}/{faq_details:ref}/faq-details-image/{upload_ref}/delete', [App\Http\Controllers\CombinationController::class, 'removeFaqDetailImage']);
            Route::delete('/combinations/{combination:ref}/delete-meta', [App\Http\Controllers\CombinationController::class, 'destroyMeta']);
        });

        // Combination Stats
        Route::get('/combinations/stats/summary', [App\Services\Reporting\Controllers\CombinationStatisticsController::class, 'summary']);

        // Page FAQs
        Route::get('/pages/{page:ref}/faqs', [App\Http\Controllers\PageFaqController::class, 'index']);
        Route::middleware(['verified.email'])->group(function () {
            Route::post('/pages/{page:ref}/store-faqs', [App\Http\Controllers\PageFaqController::class, 'store']);
            Route::patch('/pages/{page:ref}/update-faq', [App\Http\Controllers\PageFaqController::class, 'update']);
            Route::delete('/pages/{page:ref}/delete-faq', [App\Http\Controllers\PageFaqController::class, 'delete']);
        });

        // Page Stats
        Route::get('/pages/stats/summary', [App\Services\Reporting\Controllers\PageStatisticsController::class, 'summary']);

        // Users
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/create', [UserController::class, 'create']);
        Route::get('/users/{ref}/show', [UserController::class, 'show']);
        Route::get('/users/{user:email}/_show', [UserController::class, '_show']);
        Route::get('/users/export', [UserController::class, 'export']);
        Route::get('/users/{ref}/edit', [UserController::class, 'edit']);
        Route::get('/users/{user:ref}/admin-login-as', \App\Modules\User\Controllers\Actions\AdminLoginAsUser::class);

        Route::middleware(['verified.email'])->group(function () {
            Route::post('/users/store', [UserController::class, 'store'])->name('portal.admin.create.user');
            Route::put('/users/{user:ref}/update', [UserController::class, 'update']);
            Route::patch('/users/{user:ref}/update-roles', \App\Modules\User\Controllers\Actions\UpdateUserRole::class);
        });

        Route::middleware(['admin.general', 'verified.email'])->group(function () {
            Route::patch('/users/add-to-site', [\App\Modules\User\Controllers\UserController::class, 'addToSite']);
            Route::patch('/users/remove-from-site', [\App\Modules\User\Controllers\UserController::class, 'removeFromSite']);
            Route::patch('/users/restore', [\App\Modules\User\Controllers\UserController::class, 'restoreMany']);
        });
        Route::delete('/users/delete', [\App\Modules\User\Controllers\UserController::class, 'destroyMany']);

        Route::middleware(['verified.email'])->group(function () {
            Route::patch('/users/take-action', [UserController::class, 'takeAction']);

            Route::patch('/users/{user:ref}/grant-permission', \App\Modules\User\Controllers\Actions\GrantPermission::class);
            Route::patch('/users/{user:ref}/revoke-permission', \App\Modules\User\Controllers\Actions\RevokePermission::class);
            Route::patch('/users/assign-multi-roles', \App\Modules\User\Controllers\Actions\MultiAssignUserRole::class);
            Route::patch('/users/assign-multi-permissions', \App\Modules\User\Controllers\Actions\MultiAssignUserPermission::class);
        });

        // Experience
        Route::get('/experiences', [ExperienceController::class, 'index']);
        Route::get('/experiences/{experience:ref}/show', [ExperienceController::class, 'show']);
        Route::middleware(['verified.email'])->group(function () {
            Route::post('/experiences/store', [ExperienceController::class, 'store']);
            Route::patch('/experiences/{experience:ref}/update', [ExperienceController::class, 'update']);
            Route::delete('/experiences/{experience:ref}/delete', [ExperienceController::class, 'destroy']);
            Route::post('/experiences/draft', [ExperienceController::class, 'markAsDraft']);
            Route::post('/experiences/publish', [ExperienceController::class, 'markAsPublished']);
            Route::delete('/experiences/delete', [ExperienceController::class, 'destroyMany']);
            Route::post('/experiences/restore', [ExperienceController::class, 'restoreMany']);
            Route::delete('/experiences/delete/force', [ExperienceController::class, 'destroyPermanently']);
        });
        Route::get('/experiences/stats/summary', [ExperienceStatisticsController::class, 'summary']);

        // Event Experience
        Route::get('/events/{event:ref}/experiences', [App\Http\Controllers\EventExperienceController::class, 'index']);
        Route::post('/events/{event:ref}/add-experience', [App\Http\Controllers\EventExperienceController::class, 'store'])->middleware('verified.email');
        Route::patch('/events/{event:ref}/remove-experiences', [App\Http\Controllers\EventExperienceController::class, 'update'])->middleware('verified.email');

        // Permissions
        Route::get('/permissions', [App\Modules\User\Controllers\PermissionController::class, 'index']);
        Route::middleware(['verified.email'])->group(function () {
            Route::post('/permissions/store', [App\Modules\User\Controllers\PermissionController::class, 'store']);
            Route::patch('/permissions/{permission:ref}/update', [App\Modules\User\Controllers\PermissionController::class, 'update']);
            Route::delete('/permissions/{permission:ref}/delete', [App\Modules\User\Controllers\PermissionController::class, 'delete']);
        });

        // Blog
        Route::middleware(['verified.email'])->group(function () {
            Route::post('/articles/store', [App\Http\Controllers\ArticleController::class, 'store']);
            Route::patch('/articles/{article:ref}/update', [App\Http\Controllers\ArticleController::class, 'update']);
            Route::delete('/articles/{article:ref}/delete', [App\Http\Controllers\ArticleController::class, 'destroy']);
        });

        // Careers
        Route::middleware(['verified.email'])->group(function () {
            Route::post('/careers/store', [App\Http\Controllers\ApiClientCareerController::class, 'store']);
            Route::patch('/careers/{career:ref}/update', [App\Http\Controllers\ApiClientCareerController::class, 'update']);
            Route::delete('/careers/{career:ref}/delete', [App\Http\Controllers\ApiClientCareerController::class, 'destroy']);
        });

        // Team
        Route::middleware(['verified.email'])->group(function () {
            Route::post('/teammates/store', [App\Http\Controllers\TeammateController::class, 'store']);
            Route::patch('/teammates/{teammate:ref}/update', [App\Http\Controllers\TeammateController::class, 'update']);
            Route::delete('/teammates/{teammate:ref}/delete', [App\Http\Controllers\TeammateController::class, 'destroy']);
        });

        // User Stats
        Route::get('/users/stats/summary', [App\Services\Reporting\Controllers\UserStatisticsController::class, 'summary']);
        Route::get('/users/stats/chart', [App\Services\Reporting\Controllers\UserStatisticsController::class, 'chart']);

        // Dashboard Stats
        Route::get('/dashboard/stats/latest-participants', [App\Services\Reporting\Controllers\DashboardStatisticsController::class, 'latestParticipants']);

        // Redirects
        Route::get('/redirects', [App\Http\Controllers\RedirectController::class, 'index']);
        Route::get('/redirects/{redirect}/show', [App\Http\Controllers\RedirectController::class, 'show']);
        Route::get('/redirects/create', [App\Http\Controllers\RedirectController::class, 'create']);
        Route::post('/redirects/store', [App\Http\Controllers\RedirectController::class, 'store']);
        Route::post('/redirects/store-many', [App\Http\Controllers\RedirectController::class, 'storeMany']);
        Route::patch('/redirects/{redirect:ref}/update', [App\Http\Controllers\RedirectController::class, 'update']);
        Route::delete('/redirects/delete', [App\Http\Controllers\RedirectController::class, 'destroy']);

        Route::post('/events/{ref}/setup-redirect', \App\Http\Controllers\EventRedirectSetupController::class);
        Route::post('/events/categories/{ref}/setup-redirect', \App\Http\Controllers\CategoryRedirectSetupController::class);
        Route::post('/regions/{ref}/setup-redirect', \App\Http\Controllers\RegionRedirectSetupController::class);
        Route::post('/cities/{ref}/setup-redirect', \App\Http\Controllers\CityRedirectSetupController::class);
        Route::post('/venues/{ref}/setup-redirect', \App\Http\Controllers\VenueRedirectSetupController::class);
        Route::post('/combinations/{ref}/setup-redirect', \App\Http\Controllers\CombinationRedirectSetupController::class);
        Route::post('/pages/{ref}/setup-redirect', \App\Http\Controllers\PageRedirectSetupController::class);
    });

    // Charity Categories
    Route::group(['prefix' => 'charities/categories'], function () {
        Route::get('all', [CharityCategoryController::class, 'all']);
        Route::get('/', [CharityCategoryController::class, 'index']);
        Route::get('create', [CharityCategoryController::class, 'create']);
        Route::post('create', [CharityCategoryController::class, 'store'])->middleware('verified.email');
        Route::get('{id}/details', [CharityCategoryController::class, 'show']);
        Route::get('{id}/edit', [CharityCategoryController::class, 'edit']);
        Route::put('{id}/update', [CharityCategoryController::class, 'update'])->middleware('verified.email');
        Route::delete('{id}/delete', [CharityCategoryController::class, 'destroy'])->middleware('verified.email');
    });

    // Charities
    Route::group(['prefix' => 'charities'], function () {
        Route::get('all', [CharityController::class, 'all']);
        Route::get('/', [CharityController::class, 'index']);
        Route::get('create', [CharityController::class, 'create']);
        Route::post('create', [CharityController::class, 'store']);
        Route::post('{id}/details', [CharityController::class, 'show']);
        Route::put('{id}/profile/update', [CharityController::class, 'updateProfile'])->middleware('verified.email');
        Route::delete('{id}/delete', [CharityController::class, 'destroy'])->middleware('verified.email');
        Route::delete('{id}/delete/force', [CharityController::class, 'destroyPermanently'])->middleware('verified.email');
        Route::get('{id}/branding', [CharityController::class, 'branding']);
        Route::put('{id}/branding', [CharityController::class, 'updateBranding']);
        Route::get('{id}/content', [CharityController::class, 'content']);
        Route::put('{id}/content', [CharityController::class, 'updateContent']);
        Route::delete('{id}/images/{upload_id}/delete', [CharityController::class, 'removeImage']);
        Route::get('{id}/memberships', [CharityController::class, 'memberships']);
        Route::put('{id}/memberships', [CharityController::class, 'updateMemberships']);
        Route::get('{id}/fundraising/platform', [CharityController::class, 'fundraisingPlatform']);
        Route::put('{id}/fundraising/platform/update', [CharityController::class, 'updateFundraisingPlatform']);
        Route::get('{id}/events/included', [CharityController::class, 'eventsIncluded']);
        Route::post('{id}/events/included/update', [CharityController::class, 'updateEventsIncluded']);
        Route::put('{id}/external/enquiry/notifications/toggle', [CharityController::class, 'toggleExternalEnquiryNotifications']);
        Route::put('{id}/complete/registration/notifications/toggle', [CharityController::class, 'toggleCompleteRegistrationNotifications']);
        Route::put('{id}/fundraising-email/integration/toggle', [CharityController::class, 'toggleFundraisingEmailIntegration']);
        Route::put('{id}/charity-checkout/integration/toggle', [CharityController::class, 'toggleCharityCheckoutIntegration']);
        Route::get('{id}/call-notes', [CharityController::class, 'callNotes']);
        Route::put('{id}/call-notes/manager/update', [CharityController::class, 'updateManagerCallNote']);
        Route::post('{id}/call-notes/create', [CharityController::class, 'createCallNote']);
        Route::put('{id}/call-notes/{call_note_id}/update', [CharityController::class, 'updateCallNote']);
        Route::delete('{id}/call-notes/{call_note_id}/delete', [CharityController::class, 'deleteCallNote']);
        Route::get('export', [CharityController::class, 'export']);
        Route::get('{id}/invoices', [CharityController::class, 'invoices']);
        Route::get('{id}/invoices/create', [CharityController::class, 'createInvoice']);
        Route::post('{id}/invoices/create', [CharityController::class, 'storeInvoice']);
        Route::post('{id}/invoices/{invoice_id}/delete', [CharityController::class, 'deleteInvoice']);
    });

    // Charity Signups (Enquiries)
    Route::get('charity-signups', [CharitySignupController::class, 'signups']);
    Route::post('charity-signups/create', [CharitySignupController::class, 'create']);
    Route::post('charity-signups/{id}/update', [CharitySignupController::class, 'update']);
    Route::delete('charity-signups/{id}/delete', [CharitySignupController::class, 'delete']);
    Route::get('charity-signups/export', [CharitySignupController::class, 'export']);

    // Event Categories
    Route::group(['prefix' => 'events/categories'], function () {
        Route::get('getCategoriesCombination', [EventCategoryController::class, 'getCategoriesCombination']);
        Route::get('eventFetchBySlugName', [EventCategoryController::class, 'eventFetchBySlugName']);
        Route::get('all', [EventCategoryController::class, 'all']);
        Route::get('/', [EventCategoryController::class, 'index']);
        Route::get('create', [EventCategoryController::class, 'create']);
        Route::post('create', [EventCategoryController::class, 'store'])->middleware('verified.email');
        Route::get('{category:ref}/details', [EventCategoryController::class, 'show']);
        Route::get('{category:ref}/edit', [EventCategoryController::class, 'edit']);
        Route::put('{category:ref}/update', [EventCategoryController::class, 'update'])->middleware('verified.email');
        Route::post('draft', [EventCategoryController::class, 'markAsDraft'])->middleware('verified.email');
        Route::post('publish', [EventCategoryController::class, 'markAsPublished'])->middleware('verified.email');
        Route::delete('delete', [EventCategoryController::class, 'destroy'])->middleware('verified.email');
        Route::post('restore', [EventCategoryController::class, 'restore'])->middleware('verified.email');
        Route::delete('delete/force', [EventCategoryController::class, 'destroyPermanently'])->middleware('verified.email');
        Route::get('export', [EventCategoryController::class, 'export']);
        Route::delete('{category}/image/{upload_ref}/delete', [EventCategoryController::class, 'removeImage'])->middleware('verified.email');
        Route::get('{category}/national/average/create', [EventCategoryController::class, 'createNationalAverage']);
        Route::post('{category}/national/average/create', [EventCategoryController::class, 'storeNationalAverage'])->middleware('verified.email');
        Route::get('{category}/national/average/{nationalAverage}/details', [EventCategoryController::class, 'showNationalAverage']);
        Route::get('{category}/national/average/{nationalAverage}/edit', [EventCategoryController::class, 'editNationalAverage']);
        Route::put('{category}/national/average/{nationalAverage}/update', [EventCategoryController::class, 'updateNationalAverage'])->middleware('verified.email');
        Route::delete('{category}/national/average/delete', [EventCategoryController::class, 'destroyNationalAverage'])->middleware('verified.email');
        Route::get('{category}/medals', [EventCategoryController::class, 'medals']);
        Route::delete('{category:ref}/delete-faqs', [EventCategoryController::class, 'destroyManyFaqs'])->middleware('verified.email');
        Route::delete('{category:ref}/{faq:ref}/delete-faq-details', [EventCategoryController::class, 'destroyManyFaqDetails'])->middleware('verified.email');
        Route::delete('{category:ref}/{faq:ref}/{faq_details:ref}/faq-details-image/{upload_ref}/delete', [EventCategoryController::class, 'removeFaqDetailImage'])->middleware('verified.email');
        Route::get('stats/summary', [EventCategoryStatisticsController::class, 'summary']);
    });

    // Events
    Route::group(['prefix' => 'events'], function () {
        Route::get('all', [EventController::class, 'all']);
        Route::get('/', [EventController::class, 'index']);
        Route::get('upcoming', [EventController::class, 'upcoming']);
        Route::get('create', [EventController::class, 'create']);
        Route::post('create', [EventController::class, 'store']);
        Route::get('{event}/edit', [EventController::class, 'edit']);
        Route::put('{event:ref}/update', [EventController::class, 'update']);
        Route::put('{event}/reg-fields/update', [EventController::class, 'updateRegistrationFields']);
        Route::post('publish', [EventController::class, 'markAsPublished']);
        Route::post('draft', [EventController::class, 'markAsDraft']);
        Route::delete('delete', [EventController::class, 'destroy']);
        Route::post('restore', [EventController::class, 'restore']);
        Route::delete('delete/force', [EventController::class, 'destroyPermanently']);
        Route::delete('{event}/images/{upload_ref}/delete', [EventController::class, 'removeImage']);
        Route::post('{event}/duplicate', [EventController::class, 'duplicate']);
        Route::post('{event}/archive', [EventController::class, 'archive']);
        Route::post('archive', [EventController::class, 'archiveEvents']);
        Route::get('export', [EventController::class, 'export']);
        Route::get('{event}/charity/summary', [EventController::class, 'charitiesSummary']);
        Route::get('{event}/participants', [PartnerEventController::class, 'participants']);
        Route::put('{event}/charity/{charity_ref}/totalPlacesNotifications/toggle', [EventController::class, 'toggleTotalPlacesNotifications']);
        Route::get('{event}/promotionalPages/add', [EventController::class, 'addToPromotionalPages']);
        Route::get('{event}/custom-fields', [EventController::class, 'customFields']);
        Route::get('{event}/custom-field/create', [EventController::class, 'createCustomField']);
        Route::post('{event}/custom-field/store', [EventController::class, 'storeCustomField']);
        Route::get('{event}/custom-field/{event_custom_field}/edit', [EventController::class, 'editCustomField']);
        Route::put('{event}/custom-field/{event_custom_field}/update', [EventController::class, 'updateCustomField']);
        Route::put('{event}/custom-field/{event_custom_field}/status/toggle', [EventController::class, 'toggleCustomFieldStatus']);
        Route::delete('{event}/custom-field/delete', [EventController::class, 'destroyCustomField'])->withTrashed();
        Route::delete('{event}/custom-field/delete/force', [EventController::class, 'destroyCustomFieldPermanently'])->withTrashed();
        Route::get('{event}/partners', [EventController::class, 'thirdParties']);
        Route::get('{event}/partners/create', [EventController::class, 'createThirdParty']);
        Route::post('{event:ref}/partners/store', [EventController::class, 'storeThirdParty']);
        Route::get('{event}/partners/{event_third_party:ref}/edit', [EventController::class, 'editThirdParty']);
        Route::put('{event:ref}/partners/{event_third_party:ref}/update', [EventController::class, 'updateThirdParty']);
        Route::delete('{event}/partners/delete', [EventController::class, 'destroyThirdParty']);
        Route::get('{event}/medals', [EventController::class, 'medals']);
        Route::delete('{event:ref}/delete-faqs', [EventController::class, 'destroyManyFaqs']);
        Route::delete('{event:ref}/{faq:ref}/delete-faq-details', [EventController::class, 'destroyManyFaqDetails']);
        Route::delete('{event:ref}/{faq:ref}/{faq_details:ref}/faq-details-image/{upload_ref}/delete', [EventController::class, 'removeFaqDetailImage']);
        Route::get('stats/summary', [EventStatisticsController::class, 'summary']);
        Route::get('stats/chart', [EventStatisticsController::class, 'chart']);
    });

    // Partner Events
    Route::group(['prefix' => 'partner-events'], function () {
        Route::get('/', [PartnerEventController::class, 'index']);
        Route::get('participants/{event}/create', [PartnerEventController::class, 'createParticipant']);
        Route::post('participants/{event}/create', [PartnerEventController::class, 'storeParticipant'])->middleware('verified.email');
        Route::get('{event}/export', [PartnerEventController::class, 'exportEventParticipants']);
    });

    // Book Events
    Route::group(['prefix' => 'book-events'], function () {
        Route::get('/', [PartnerEventController::class, 'index']);
        Route::get('participants/{event}/register', [BookEventController::class, 'register']);
        Route::post('participants/{event}/register', [BookEventController::class, 'store'])->middleware('verified.email');
    });

    // Participants
    Route::group(['prefix' => 'participants'], function () {
        Route::get('/', [ParticipantController::class, 'index']);
        Route::get('{participant}/download', [ParticipantController::class, 'download'])->withTrashed();
        Route::get('{participant}/edit', [ParticipantController::class, 'edit']);
        Route::put('{participant}/update', [ParticipantController::class, 'update'])->middleware('verified.email');
        Route::post('{participant}/transfer', [ParticipantController::class, 'transfer'])->middleware('verified.email');
        Route::post('{participant}/verify-transfer', [ParticipantController::class, 'verifyTransfer'])->middleware('verified.email');
        Route::delete('delete', [ParticipantController::class, 'destroy'])->middleware('verified.email');
        Route::post('restore', [ParticipantController::class, 'restore'])->middleware('verified.email');
        Route::delete('delete/force', [ParticipantController::class, 'destroyPermanently'])->middleware('verified.email');
        Route::get('{participant}/family-registration/new', [ParticipantController::class, 'createFamilyMember']);
        Route::post('{participant}/family-registration/new', [ParticipantController::class, 'storeFamilyMember'])->middleware('verified.email');
        Route::get('{participant}/family-registration/{familyRegistrationId}/edit', [ParticipantController::class, 'editFamilyMember']);
        Route::put('{participant}/family-registration/{familyRegistrationId}/update', [ParticipantController::class, 'updateFamilyMember'])->middleware('verified.email');
        Route::delete('{participant}/family-registration/{familyRegistrationId}/delete', [ParticipantController::class, 'deleteFamilyMember'])->middleware('verified.email');
        Route::get('export', [ParticipantController::class, 'export']);
        Route::post('notify', [ParticipantController::class, 'notify'])->middleware('verified.email');
        Route::post('{participant}/place/offer', [ParticipantController::class, 'offerPlace'])->middleware('verified.email');
        Route::get('stats/summary', [ParticipantStatisticsController::class, 'summary']);
        Route::get('stats/chart', [ParticipantStatisticsController::class, 'chart']);
    });

    // Profile
    Route::group(['prefix' => 'profile'], function () {
        Route::get('invoices', [ProfileController::class, 'invoices']);
    });

    // Entries
    Route::group(['prefix' => 'entries'], function () {
        Route::get('/', [EntryController::class, 'index']);
        Route::get('{participant}/edit', [EntryController::class, 'edit']);
        Route::put('{participant:ref}/update', [EntryController::class, 'update'])->middleware('verified.email');
        Route::post('{participant}/verify-transfer', [EntryController::class, 'verifyTransfer'])->middleware('verified.email');
        Route::post('{participant}/transfer', [EntryController::class, 'transfer'])->middleware('verified.email');
        Route::get('{participant}/download', [EntryController::class, 'download']);
        Route::delete('delete', [EntryController::class, 'destroy'])->middleware('verified.email');
        Route::get('stats/summary', [EntryStatisticsController::class, 'summary']);
        Route::get('stats/chart', [EntryStatisticsController::class, 'chart']);
    });

    // For Stripe Payment (Adding Card) Route
    Route::get('getUserAllCardDetails', [\App\Http\Controllers\StripePaymentController::class, 'getUserAllCardDetails']);
    Route::post('addUserCardDetail', [\App\Http\Controllers\StripePaymentController::class, 'addUserCardDetail']);
    Route::post('deleteUserCardDetails', [\App\Http\Controllers\StripePaymentController::class, 'deleteUserCardDetails']);
    Route::post('updateUserCardDetails', [\App\Http\Controllers\StripePaymentController::class, 'updateUserCardDetails']);

    Route::get('getCustomerPaymentMethod', [\App\Http\Controllers\StripePaymentController::class, 'getCustomerPaymentMethod']);
    Route::post('changeDefaultPaymentMethod', [\App\Http\Controllers\StripePaymentController::class, 'changeDefaultPaymentMethod']);

    // Invoices
    Route::group(['prefix' => 'invoices'], function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('{ref}/edit', [InvoiceController::class, 'edit']);
        Route::put('{ref}/update', [InvoiceController::class, 'update'])->middleware('verified.email');
        Route::delete('delete', [InvoiceController::class, 'destroy'])->middleware('verified.email');
        Route::post('restore', [InvoiceController::class, 'restore'])->middleware('verified.email');
        Route::delete('delete/force', [InvoiceController::class, 'destroyPermanently'])->middleware('verified.email');
        Route::get('{ref}/download', [InvoiceController::class, 'download']);
        Route::get('export', [InvoiceController::class, 'export']);
        Route::get('{ref}/pdf/generate', [InvoiceController::class, 'generateInvoicePdf']);
        Route::get('{ref}/pay', [InvoiceController::class, 'pay']);
        Route::delete('{ref}/items/delete', [InvoiceController::class, 'destroyInvoiceItem'])->middleware('verified.email');
        Route::get('{ref}/item/create', [InvoiceController::class, 'createInvoiceItem']);
        Route::post('{ref}/item/create', [InvoiceController::class, 'storeInvoiceItem'])->middleware('verified.email');
        Route::get('{ref}/item/{invoiceItemRef}/edit', [InvoiceController::class, 'editInvoiceItem']);
        Route::put('{ref}/item/{invoiceItemRef}/update', [InvoiceController::class, 'updateInvoiceItem'])->middleware('verified.email');
        Route::get('/charity/memberships', [InvoiceController::class, 'charityMemberships']);
        Route::get('/participants', [InvoiceController::class, 'participants']);
        Route::get('/charity/partner-packages/', [InvoiceController::class, 'charityPartnerPackages']);
        Route::get('/resale/requests', [InvoiceController::class, 'resaleRequests']);
        Route::get('/event-place-invoices', [InvoiceController::class, 'eventPlaceInvoices']);
        Route::get('stats/summary', [InvoiceStatisticsController::class, 'summary']);
        Route::get('stats/chart', [InvoiceStatisticsController::class, 'chart']);
    });

    // Market Place
    Route::group(['prefix' => 'market'], function () {
        Route::get('/', [MarketController::class, 'index']);
        Route::group(['prefix' => 'manage'], function () {
            Route::get('resale/places', [ResalePlaceController::class, 'index']);
            Route::get('resale/places/create', [ResalePlaceController::class, 'create']);
            Route::post('resale/places/create', [ResalePlaceController::class, 'store'])->middleware('verified.email');
            Route::get('resale/places/{id}/edit', [ResalePlaceController::class, 'edit']);
            Route::put('resale/places/{id}/update', [ResalePlaceController::class, 'update'])->middleware('verified.email');
            Route::delete('{id}/delete', [ResalePlaceController::class, 'destroy'])->middleware('verified.email');
            Route::get('notifications', [MarketController::class, 'notifications']);
            Route::post('notifications/toggle', [MarketController::class, 'toggleNotifications'])->middleware('verified.email');
        });

        // Route::get('{id}/details', [MarketController::class, 'show']);
        // Route::get('{id}/edit', [MarketController::class, 'edit']);
        // Route::put('{id}/update', [MarketController::class, 'update']);
        // Route::delete('{id}/delete', [MarketController::class, 'destroy']);
        // Route::delete('{id}/delete/force', [MarketController::class, 'destroyPermanently']);
        // Route::get('export', [MarketController::class, 'export']);
        // Route::get('{id}/generate', [MarketController::class, 'generateInvoice']);
    });

    // Roles
    Route::group(['prefix' => 'roles'], function () {
        Route::get('/', [RoleController::class, 'roles']);
        Route::post('create', [RoleController::class, 'create'])->middleware('verified.email');
        Route::get('{name}/details', [RoleController::class, 'role']);
        Route::put('{_name}/update', [RoleController::class, 'update'])->middleware('verified.email');
        Route::delete('{name}/delete', [RoleController::class, 'delete'])->middleware('verified.email');
    });

    // Permissions
//    Route::get('permissions', [PermissionController::class, 'permissions']);
//    Route::post('permissions/create', [PermissionController::class, 'create']);
//    Route::get('permissions/{name}/details', [PermissionController::class, 'permission']);
//    Route::put('permissions/{_name}/update', [PermissionController::class, 'update']);
//    Route::delete('permissions/{name}/delete', [PermissionController::class, 'delete']);

    // Settings
    Route::group(['prefix' => 'settings'], function () {
        Route::get('details', [SettingController::class, 'show']);
        Route::get('socials', [SettingController::class, 'socials']);
        Route::post('socials/update', [SettingController::class, 'updateSocials'])->middleware('verified.email');
        Route::delete('socials/{id}/delete', [SettingController::class, 'destroySocial'])->middleware('verified.email');
        Route::post('custom-fields/update', [SettingController::class, 'updateCustomFields'])->middleware('verified.email');
        Route::delete('custom-fields/{id}/delete', [SettingController::class, 'destroyCustomField'])->middleware('verified.email');

        // Route::get('dashboards', [SettingController::class, 'dashboards']);
        // Route::get('dashboards/{id}', [SettingController::class, 'dashboard']);
        // Route::put('dashboards/{id}/update', [SettingController::class, 'updateDashboard']);
        // Route::get('events', [SettingController::class, 'events']);
        // Route::post('events/update', [SettingController::class, 'updateEvents']);
        // Route::get('vmm', [SettingController::class, 'vmm']);
        // Route::post('vmm/update', [SettingController::class, 'updateVmm']);
        // Route::get('vmm', [SettingController::class, 'vmm']);
        // Route::get('fundraisingEmails', [DripController::class, 'dripEmails']);
        // Route::post('fundraisingEmails/create', [DripController::class, 'create']);
        // Route::get('fundraisingEmails/{id}/details', [DripController::class, 'dripEmail']);
        // Route::put('fundraisingEmails/{id}/update', [DripController::class, 'update']);
        // Route::delete('fundraisingEmails/{id}/delete', [DripController::class, 'delete']);
    });

    // Regions
    Route::group(['prefix' => 'regions'], function () {
        Route::get('/all', [RegionController::class, 'all']);
        Route::get('/', [RegionController::class, 'index']);
        Route::get('create', [RegionController::class, 'create']);
        Route::post('create', [RegionController::class, 'store'])->middleware('verified.email');
        Route::get('{region}/details', [RegionController::class, 'show']);
        Route::get('{ref}/edit', [RegionController::class, 'edit']);
        Route::get('export', [RegionController::class, 'export']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('{region:ref}/update', [RegionController::class, 'update']);
            Route::post('draft', [RegionController::class, 'markAsDraft']);
            Route::post('publish', [RegionController::class, 'markAsPublished']);
            Route::delete('delete', [RegionController::class, 'destroy']);
            Route::post('restore', [RegionController::class, 'restore']);
            Route::delete('delete/force', [RegionController::class, 'destroyPermanently']);
            Route::delete('{region:ref}/image/{upload:ref}/delete', [RegionController::class, 'removeImage'])->withoutScopedBindings();
            Route::delete('{region:ref}/delete-faqs', [RegionController::class, 'destroyManyFaqs']);
            Route::delete('{region:ref}/{faq:ref}/delete-faq-details', [RegionController::class, 'destroyManyFaqDetails']);
            Route::delete('{region:ref}/{faq:ref}/{faq_details:ref}/faq-details-image/{upload_ref}/delete', [RegionController::class, 'removeFaqDetailImage']);
        });
        Route::get('stats/summary', [RegionStatisticsController::class, 'summary']);
    });

    // Venues
    Route::group(['prefix' => 'venues'], function () {
        Route::get('/all', [VenueController::class, 'all']);
        Route::get('/', [VenueController::class, 'index']);
        Route::get('create', [VenueController::class, 'create']);
        Route::post('create', [VenueController::class, 'store'])->middleware('verified.email');
        Route::get('{ref}/details', [VenueController::class, 'show']);
        Route::get('{ref}/edit', [VenueController::class, 'edit']);
        Route::get('export', [VenueController::class, 'export']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('{venue:ref}/update', [VenueController::class, 'update']);
            Route::post('draft', [VenueController::class, 'markAsDraft']);
            Route::post('publish', [VenueController::class, 'markAsPublished']);
            Route::delete('delete', [VenueController::class, 'destroy']);
            Route::post('restore', [VenueController::class, 'restore']);
            Route::delete('delete/force', [VenueController::class, 'destroyPermanently']);
            Route::delete('{venue:ref}/image/{upload:ref}/delete', [VenueController::class, 'removeImage'])->withoutScopedBindings();
            Route::delete('{venue:ref}/delete-faqs', [\App\Http\Controllers\VenueController::class, 'destroyManyFaqs']);
            Route::delete('{venue:ref}/{faq:ref}/delete-faq-details', [\App\Http\Controllers\VenueController::class, 'destroyManyFaqDetails']);
            Route::delete('{venue:ref}/{faq:ref}/{faq_details:ref}/faq-details-image/{upload_ref}/delete', [\App\Http\Controllers\VenueController::class, 'removeFaqDetailImage']);
        });
        Route::get('stats/summary', [VenueStatisticsController::class, 'summary']);
    });

    // Cities
    Route::group(['prefix' => 'cities'], function() {
        Route::get('/all', [\App\Http\Controllers\CityController::class, 'all']);
        Route::get('/', [\App\Http\Controllers\CityController::class, 'index']);
        Route::get('create', [\App\Http\Controllers\CityController::class, 'create']);
        Route::post('create', [\App\Http\Controllers\CityController::class, 'store'])->middleware('verified.email');
        Route::get('{ref}/details', [\App\Http\Controllers\CityController::class, 'show']);
        Route::get('{ref}/edit', [\App\Http\Controllers\CityController::class, 'edit']);
        Route::get('export', [\App\Http\Controllers\CityController::class, 'export']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('{city:ref}/update', [\App\Http\Controllers\CityController::class, 'update']);
            Route::post('draft', [\App\Http\Controllers\CityController::class, 'markAsDraft']);
            Route::post('publish', [\App\Http\Controllers\CityController::class, 'markAsPublished']);
            Route::delete('delete', [\App\Http\Controllers\CityController::class, 'destroy']);
            Route::post('restore', [\App\Http\Controllers\CityController::class, 'restore']);
            Route::delete('delete/force', [\App\Http\Controllers\CityController::class, 'destroyPermanently']);
            Route::delete('{city:ref}/image/{upload:ref}/delete', [\App\Http\Controllers\CityController::class, 'removeImage'])->withoutScopedBindings();
            Route::delete('{city:ref}/delete-faqs', [\App\Http\Controllers\CityController::class, 'destroyManyFaqs']);
            Route::delete('{city:ref}/{faq:ref}/delete-faq-details', [\App\Http\Controllers\CityController::class, 'destroyManyFaqDetails']);
            Route::delete('{city:ref}/{faq:ref}/{faq_details:ref}/faq-details-image/{upload_ref}/delete', [\App\Http\Controllers\CityController::class, 'removeFaqDetailImage']);
        });
        Route::get('stats/summary', [CityStatisticsController::class, 'summary']);
    });

    // Series
    Route::group(['prefix' => 'series'], function() {
        Route::get('/', [SerieController::class, 'index']);
        Route::get('/all', [SerieController::class, 'all']);
        Route::get('create', [SerieController::class, 'create']);
        Route::post('create', [SerieController::class, 'store'])->middleware('verified.email');
        Route::get('{ref}/details', [SerieController::class, 'show']);
        Route::get('{ref}/edit', [SerieController::class, 'edit']);
        Route::get('export', [SerieController::class, 'export']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('{ref}/update', [SerieController::class, 'update']);
            Route::post('draft', [SerieController::class, 'markAsDraft']);
            Route::post('publish', [SerieController::class, 'markAsPublished']);
            Route::delete('delete', [SerieController::class, 'destroy']);
            Route::post('restore', [SerieController::class, 'restore']);
            Route::delete('delete/force', [SerieController::class, 'destroyPermanently']);
        });
        Route::get('stats/summary', [SerieStatisticsController::class, 'summary']);
    });

    // Sponsors
    Route::group(['prefix' => 'sponsors'], function() {
        Route::get('/', [SponsorController::class, 'index']);
        Route::get('/all', [SponsorController::class, 'all']);
        Route::get('create', [SponsorController::class, 'create']);
        Route::post('create', [SponsorController::class, 'store'])->middleware('verified.email');
        Route::get('{ref}/details', [SponsorController::class, 'show']);
        Route::get('{ref}/edit', [SponsorController::class, 'edit']);
        Route::get('export', [SponsorController::class, 'export']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('{ref}/update', [SponsorController::class, 'update']);
            Route::post('draft', [SponsorController::class, 'markAsDraft']);
            Route::post('publish', [SponsorController::class, 'markAsPublished']);
            Route::delete('delete', [SponsorController::class, 'destroy']);
            Route::post('restore', [SponsorController::class, 'restore']);
            Route::delete('delete/force', [SponsorController::class, 'destroyPermanently']);
        });
        Route::get('stats/summary', [SponsorStatisticsController::class, 'summary']);
    });

    // Medals
    Route::group(['prefix' => 'medals'], function () {
        Route::get('/', [MedalController::class, 'index']);
        Route::get('create', [MedalController::class, 'create']);
        Route::post('create', [MedalController::class, 'store'])->middleware('verified.email');
        Route::get('{ref}/details', [MedalController::class, 'show']);
        Route::get('{ref}/edit', [MedalController::class, 'edit']);
        Route::get('export', [MedalController::class, 'export']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('{ref}/update', [MedalController::class, 'update']);
            Route::post('draft', [MedalController::class, 'markAsDraft']);
            Route::post('publish', [MedalController::class, 'markAsPublished']);
            Route::delete('delete', [MedalController::class, 'destroy']);
            Route::post('restore', [MedalController::class, 'restoreMany']);
            Route::delete('delete/force', [MedalController::class,  'destroyPermenantly']);
        });
        Route::get('stats/summary', [MedalStatisticsController::class, 'summary']);
    });

    // Enquiries
    Route::group(['prefix' => 'enquiries'], function () {
        Route::group(['prefix' => 'external'], function () {
            Route::get('/', [ExternalEnquiryController::class, 'index']);
            Route::get('create', [ExternalEnquiryController::class, 'create']);
            Route::post('create', [ExternalEnquiryController::class, 'store'])->middleware('verified.email');
            Route::get('{ref}/edit', [ExternalEnquiryController::class, 'edit']);
            Route::get('export', [ExternalEnquiryController::class, 'export']);

            Route::middleware(['verified.email'])->group(function () {
                Route::post('{ref}/update', [ExternalEnquiryController::class, 'update']);
                Route::delete('delete', [ExternalEnquiryController::class, 'destroy']);
                Route::post('restore', [ExternalEnquiryController::class, 'restore']);
                Route::delete('delete/force', [ExternalEnquiryController::class, 'destroyPermanently']);
                Route::post('{ref}/place/offer', [ExternalEnquiryController::class, 'offerPlace']);
            });
            Route::get('stats/summary', [ExternalEnquiryStatisticsController::class, 'summary']);
            Route::get('stats/chart', [ExternalEnquiryStatisticsController::class, 'chart']);
        });

        Route::group(['prefix' => 'charity'], function () {

        });

        Route::group(['prefix' => 'event'], function () {

        });

        Route::group(['prefix' => 'partner'], function () {

        });

        // Website Enquiries
        Route::group([], function () {
            Route::get('/', [EnquiryController::class, 'index']);
            Route::get('create', [EnquiryController::class, 'create']);
            Route::post('create', [EnquiryController::class, 'store'])->middleware('verified.email');
            Route::get('{ref}/edit', [EnquiryController::class, 'edit']);
            Route::get('export', [EnquiryController::class, 'export']);

            Route::middleware(['verified.email'])->group(function () {
                Route::post('{enquiry:ref}/update', [EnquiryController::class, 'update']);
                Route::delete('delete', [EnquiryController::class, 'destroy']);
                Route::post('restore', [EnquiryController::class, 'restore']);
                Route::delete('delete/force', [EnquiryController::class, 'destroyPermanently']);
                Route::post('{ref}/place/offer', [EnquiryController::class, 'offerPlace']);
            });
            Route::get('stats/summary', [EnquiryStatisticsController::class, 'summary']);
            Route::get('stats/chart', [EnquiryStatisticsController::class, 'chart']);
        });
    });

    // Partners
    Route::group(['prefix' => 'partners'], function () {
        Route::get('all', [PartnerController::class, 'all']);
        Route::get('/', [PartnerController::class, 'index']);
        Route::get('create', [PartnerController::class, 'create']);
        Route::post('create', [PartnerController::class, 'store'])->middleware('verified.email');
        Route::get('{ref}/details', [PartnerController::class, 'show']);
        Route::get('{ref}/edit', [PartnerController::class, 'edit']);
        Route::get('export', [PartnerController::class, 'export']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('{partner:ref}/update', [PartnerController::class, 'update']);
            Route::delete('delete', [PartnerController::class, 'destroy']);
            Route::post('restore', [PartnerController::class, 'restore']);
            Route::delete('delete/force', [PartnerController::class, 'destroyPermanently']);
            Route::delete('{partner:ref}/image/{upload_ref}/delete', [PartnerController::class, 'removeImage']);
        });
        Route::get('stats/summary', [PartnerStatisticsController::class, 'summary']);
    });

    // Partner Channels
    Route::group(['prefix' => 'partner-channels'], function () {
        Route::get('all', [PartnerChannelController::class, 'all']);
        Route::get('/', [PartnerChannelController::class, 'index']);
        Route::get('create', [PartnerChannelController::class, 'create']);
        Route::post('create', [PartnerChannelController::class, 'store'])->middleware('verified.email');
         Route::get('{ref}/edit', [PartnerChannelController::class, 'edit']);

        Route::middleware(['verified.email'])->group(function () {
            Route::put('{channel:ref}/update', [PartnerChannelController::class, 'update']);
            Route::delete('delete', [PartnerChannelController::class, 'destroy']);
        });
    });

    // Finances
    Route::group(['prefix' => 'finances'], function() {
        Route::get('meta', [FinanceController::class, 'metaData']);
        Route::get('balance', [FinanceController::class, 'balance']);
        Route::get('balance/infinite', [FinanceController::class, 'infiniteBalance']);
        Route::get('balance/finite', [FinanceController::class, 'finiteBalance']);
        Route::get('accounts/{type}/history', [FinanceController::class, 'accountsHistory']);
    });
});

Route::group(['middleware' => []], function() {
    /**
     * @group Testing
     * Testing sections on the application
     * @authenticated
     *
     * @param  \Illuminate\Http\Request  $request
     */
    Route::post('testing', function(\Illuminate\Http\Request $request) {

        $data = [
            [
                'name' => 'Test',
                'email' => 'mesmer@gmail.com'
            ],
            [
                'name' => 'Test 2',
                'email' => ''
            ]
        ];

        return response()->json(gettype(collect($data)->firstWhere('email', 'mesmer2@gmail.com')), 200);

        return response()->json(\Carbon\Carbon::parse(strtotime("1hr 10 mins", 0))->toTimeString(), 200);
    });
});
