<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\VenueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\EventController;
use App\Http\Controllers\Portal\RegionController;
use App\Http\Controllers\Portal\CharityController;
use App\Modules\Partner\Controllers\PartnerController;
use App\Http\Controllers\Portal\EventCategoryController;
use App\Http\Controllers\Client\CharitySignupController;
use App\Http\Controllers\Client\RedirectController;
use App\Http\Controllers\Client\SearchController;
use App\Http\Controllers\Portal\CharityCategoryController;
use App\Http\Controllers\Portal\EventController as PortalEventController;

/*
|--------------------------------------------------------------------------
| Client Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Client routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Search
Route::get('search/{option?}', SearchController::class);
Route::post('search/history/store', [SearchController::class, 'storeSearchHistory']);
Route::delete('search/history/clear', [SearchController::class, 'clearSearchHistory']);

Route::middleware(['redirect'])->group(function() {
    Route::group([], function() {
        // Charity Signups (Enquiries)
        Route::post('charity-signups/create', [CharitySignupController::class, 'create']);
    });

    Route::group(['prefix' => 'charities'], function() {
        Route::get('all', [CharityController::class, 'all']);
        Route::get('/', [CharityController::class, '_index']);

        Route::group(['prefix' => 'categories'], function() {
            Route::get('/', [CharityCategoryController::class, '_index']);
            Route::get('all', [CharityCategoryController::class, 'all']);
            Route::get('{category:slug}', [CharityCategoryController::class, 'charities']);
        });
    });

    Route::get('getRaceInfoWebsite', [App\Http\Controllers\MediaLibraryController::class, 'getRaceInfoWebsite']);

// Region
    Route::group(['prefix' => 'regions'], function() {
        Route::get('all', [RegionController::class, 'all']);
        Route::get('/', [RegionController::class, '_index']);
        Route::get('{slug}', [RegionController::class, 'events']);
    });

// Venue
    Route::group(['prefix' => 'venues'], function() {
        Route::get('all', [VenueController::class, 'all']);
        Route::get('/', [VenueController::class, '_index']);
        Route::get('{slug}', [VenueController::class, 'events']);
    });

// City
    Route::group(['prefix' => 'cities'], function() {
        Route::get('all', [CityController::class, 'all']);
        Route::get('/', [CityController::class, '_index']);
        Route::get('{slug}', [CityController::class, 'events']);
    });

    Route::group(['prefix' => 'events'], function() {
        Route::group(['prefix' => 'categories'], function() {
            Route::get('all', [EventCategoryController::class, 'all']);
            Route::get('/', [EventCategoryController::class, '_index']);
            Route::get('getPopularCombination', [EventCategoryController::class, 'getPopularCombination']);
            Route::get('getCustomFilterMenus', [EventCategoryController::class, 'getCustomFilterMenus']);
            Route::get('{_category}', [EventCategoryController::class, 'events']);
        });

        Route::get('all', [PortalEventController::class, 'all']);
        Route::get('/', [EventController::class, 'index']);
        Route::get('calendar', [EventController::class, 'calendar']);
        Route::get('upcoming', [EventController::class, 'upcoming']);
        Route::get('next', [EventController::class, 'next']);
        Route::get('popular', [EventController::class, 'popular']);
        Route::get('{event}', [EventController::class, 'show']);
        Route::post('ldt/checkout', [EventController::class, 'checkoutOnLDT']);
    });
    Route::post('seo/redirect', [RedirectController::class, 'redirect']);
    Route::post('directRedirectUrl', [RedirectController::class, 'directRedirectUrl']);

    Route::get('/experiences', [App\Http\Controllers\ExperienceController::class, 'index']);

// FAQs
    Route::get('/faqs', [App\Http\Controllers\FaqController::class, 'index']);

// Contact Us
    Route::get('/enquiries/options', [App\Http\Controllers\EnquiryController::class, 'create']);
    Route::post('/enquiries/store', [App\Http\Controllers\EnquiryController::class, 'store']);

// Careers
    Route::get('/careers', [App\Http\Controllers\ApiClientCareerController::class, 'index']);
    Route::get('/careers/{career:ref}', [App\Http\Controllers\ApiClientCareerController::class, 'show']);

// Team
    Route::get('/team', [App\Http\Controllers\TeammateController::class, 'index']);
    Route::get('/teammates/{teammate:ref}/show', [App\Http\Controllers\TeammateController::class, 'show']);

// Blog
    Route::get('/articles', [App\Http\Controllers\ArticleController::class, 'index']);
    Route::get('/articles/{article:ref}/show', [App\Http\Controllers\ArticleController::class, 'show']);

// Pages
    Route::get('/pages/{ref}/show', [App\Http\Controllers\PageController::class, '_show'])->name('client.pages.show');
    Route::post('/pages/fetch-by-url', App\Http\Controllers\FetchPageByUrlActionController::class);

// Customize Page
    Route::get('/customizePages/getMenusShow', [App\Http\Controllers\PageController::class, 'getMenusShow']);
    Route::post('/customizePages/getDetailsByMenus', [App\Http\Controllers\PageController::class, 'getDetailsByMenus']);

// Combinations
    Route::get('/combinations', [App\Http\Controllers\CombinationController::class, '_index'])->name('client.combinations.index');
    Route::get('/combinations/{combination}', [App\Http\Controllers\CombinationController::class, '_show'])->name('client.combinations.show');
    Route::post('/combinations/fetch-by-path', [App\Http\Controllers\CombinationController::class, '_showByPath'])->name('client.combinations.show_by_path');
    Route::get('/combinations/{path}/fetch-by-path', [App\Http\Controllers\CombinationController::class, '_showByPathGet'])
        ->name('client.combinations.show_by_path_get')
        ->where('path', '.*');

});
