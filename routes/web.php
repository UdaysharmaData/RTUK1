<?php

use App\Enums\RoleNameEnum;
use App\Modules\Setting\Enums\SiteCodeEnum;
use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\UploadImageSizeVariantEnum;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Http\Controllers\TestController;
use App\Http\Helpers\MailHelper;
use App\Models\Location;
use App\Models\Medal;
use App\Models\Upload;
use App\Models\Uploadable;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Finance\Enums\OngoingExternalTransactionStatusEnum;
use App\Modules\Finance\Models\OngoingExternalTransaction;
use App\Modules\Participant\Models\Participant;
use App\Modules\Setting\Enums\OrganisationCodeEnum;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Modules\Setting\Models\Organisation;
use App\Modules\Setting\Models\Site;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\RoleUser;
use App\Modules\User\Models\User;
use App\Scopes\SiteScope;
use Carbon\Carbon;
use Google\Service\Compute\Help;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});
//
//Auth::routes();
//
//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

//Route::get('/request', function(\Illuminate\Http\Request $request) {
//    return response()->json([
//        'path' => $request->path(),
//        'url' => $request->url(),
//        'host' => $request->host(),
//        'httpHost' => $request->httpHost(),
//        'schemeAndHttpHost' => $request->schemeAndHttpHost(),
//        'ip' => $request->ip(),
//        'origin' => $request->headers->get('Origin'),
//        'request' => $request
//    ], 200);
//});

Route::get('/auth/redirect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirect']);
Route::get('/auth/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'callback']);

//Route::get('/socials/redirect', [\App\Http\Controllers\SocialsConnectController::class, 'redirect']);
//Route::get('/socials/callback', [\App\Http\Controllers\SocialsConnectController::class, 'callback']);

//Route::get('/callback', function (\Illuminate\Support\Facades\Request $request) { // todo: inconclusive testing...
//    $state = $request->session()->pull('state');
//
//    throw_unless(
//        strlen($state) > 0 && $state === $request->state,
//        InvalidArgumentException::class
//    );
//
//    $response = \Illuminate\Support\Facades\Http::asForm()->post('http://api.test/oauth/token', [
//        'grant_type' => 'authorization_code',
//        'client_id' => 'client-id',
//        'client_secret' => 'client-secret',
//        'redirect_uri' => 'http://sport-for-api.test/callback',
//        'code' => $request->code,
//    ]);
//
//    return $response->json();
//});

//Route::get('/redirect', function (\Illuminate\Support\Facades\Request $request) {
//    $request->session()->put('state', $state = Str::random(40));
//
//    $query = http_build_query([
//        'client_id' => 'client-id',
//        'redirect_uri' => 'http://sport-for-api.test/login',
//        'response_type' => 'code',
//        'scope' => '',
//        'state' => $state,
//    ]);
//
//    return redirect('http://api.test/oauth/authorize?' . $query);
//});

Route::get('/invoice/pdf', [TestController::class, 'invoicePdfView']);

Route::get('/invoice', [TestController::class, 'invoiceView']);

Route::get('total-places-exhausted-mail', [TestController::class, 'totalPlacesExhaustedMail']);

Route::get('attempt-registered-deleted-account-mail', [TestController::class, 'attemptedRegisteredDeletedAccountMail']);

Route::get('charity-places-exhausted-mail', [TestController::class, 'charityPlacesExhaustedMail']);

Route::get('participant-registration-mail', [TestController::class, 'participantRegistrationMail']);

Route::get('uncompleted-registration-mail', [TestController::class, 'uncompletedRegistrationMail']);

Route::get('account-created-mail', [TestController::class, 'accountCreatedMail']);

Route::get('event-archived-mail', [TestController::class, 'eventArchivedMail']);

Route::get('membership-expired-mail', [TestController::class, 'membershipExpiredMail']);

Route::get('attempt-registration-mail', [TestController::class, 'attemptRegistrationMail']);

Route::get('failed-to-offer-places-to-ldt', [TestController::class, 'failedToOfferPlacesToLDT']);

Route::get('/email', function () {
    $mailHelper = new MailHelper();

    return view('mails.email-example', [
        'mailHelper' => $mailHelper,
        'member' => $mailHelper->developerMember(),
    ]);
});

//Route::get('/revert/{migration}', function (string $migration) {
//    $query = \Illuminate\Support\Facades\DB::table('migrations')
//        ->where('migration', '=', $migration);
//
//    if ($query->exists()) {
//        $query->delete();
//        echo "Migration record [$migration] deleted!";
//    } else echo "Migration record [$migration] not found in DB.";
//});// /revert/2022_10_03_091310_add_passport_number_and_bio_column_to_profiles_table

// Route::get('/fix-pages', function () {
//     $query = \Illuminate\Support\Facades\DB::table('migrations')
//         ->where('migration', '=', '2022_12_01_141903_create_pages_table');
//     if ($query->exists()) $query->delete();
//     echo "Migration removed!";

//     \Illuminate\Support\Facades\Schema::dropIfExists('pages');
//     echo "Existing table dropped!";

//     \Illuminate\Support\Facades\Artisan::call('migrate --path=/database/migrations/2022_12_01_141903_create_pages_table.php');
//     echo "Migration complete!";
// });

// Route::get('/fix-migrations', function () {
//     $migrations = [
//         '2022_08_11_074346_create_views_table',
//         '2022_11_08_115404_create_interactions_table',
//         '2022_11_08_115454_create_analytics_metadata_table',
//         '2023_01_12_172606_create_analytics_total_counts_table',
//         '2022_12_01_141903_create_pages_table'
//     ];

//     \Illuminate\Support\Facades\DB::table('migrations')
//         ->where('migration', '=', '2023_01_09_174816_add_type_column_to_interactions_table')
//         ->delete();

//     foreach ($migrations as $key => $migration) {
//         \Illuminate\Support\Facades\DB::table('migrations')
//             ->where('migration', '=', $migration)
//             ->delete();
//         echo "Migration [$key] removed!". PHP_EOL;
//     }

//     \Illuminate\Support\Facades\Schema::dropIfExists('views');
//     \Illuminate\Support\Facades\Schema::dropIfExists('interactions');
//     \Illuminate\Support\Facades\Schema::dropIfExists('analytics_metadata');
//     \Illuminate\Support\Facades\Schema::dropIfExists('analytics_total_counts');
//     \Illuminate\Support\Facades\Schema::dropIfExists('pages');
//     echo "Existing tables dropped!". PHP_EOL;

//     foreach ($migrations as $key => $migration) {
//         \Illuminate\Support\Facades\Artisan::call("migrate --path=/database/migrations/$migration.php");
//         echo "Migration [$key] completed!". PHP_EOL;
//     }
// });

// Route::get('/fix-mesmer-migrations', function () {
//     $migrations = [
//         '2022_12_02_103115_modify_external_id_column_in_event_third_parties_table',
//         '2022_12_09_090820_add_rule_column_to_event_custom_fields_table',
//         '2022_12_12_104733_drop_key_column_from_participant_custom_fields_table',
//         '2022_12_16_060426_modify_columns_in_external_enquiries_table',
//         '2022_12_16_090515_add_deleted_at_column_to_enquiries_table',
//         '2022_12_16_090530_add_deleted_at_column_to_charity_enquiries_table',
//         '2022_12_16_090622_add_deleted_at_column_to_event_enquiries_table',
//         '2022_12_16_090631_add_deleted_at_column_to_partner_enquiries_table',
//         '2022_12_21_093929_rename_options_column_in_event_custom_fields_table',

//         '2022_04_25_135535_create_participant_profiles',
//         '2022_04_28_084744_create_enquiries_table',
//         '2022_04_28_082928_create_charity_enquiries_table',
//         '2022_04_28_184303_create_partners_table',
//         '2022_04_28_184305_create_partner_packages_table',
//         '2022_04_28_184356_create_charity_partner_package_table',
//         '2022_05_05_150138_create_partner_enquiries_table',
//         '2022_05_16_114946_create_event_managers_table',
//         '2022_05_16_114948_create_event_event_manager_table',
//         '2022_05_16_154948_create_event_enquiries_table',
//         '2022_05_16_100758_create_event_custom_fields_table',
//         '2022_08_01_145602_create_participants_table',
//         '2022_08_01_165704_create_participant_custom_fields_table',
//         '2022_08_25_114958_create_setting_custom_fields_table',
//     ];

//     foreach ($migrations as $key => $migration) {
//         \Illuminate\Support\Facades\DB::table('migrations')
//             ->where('migration', '=', $migration)
//             ->delete();
//         echo "Mesmer Migration [$key] removed!". PHP_EOL;
//     }

//     \Illuminate\Support\Facades\Schema::dropIfExists('participant_profiles');
//     \Illuminate\Support\Facades\Schema::dropIfExists('external_enquiries');
//     \Illuminate\Support\Facades\Schema::dropIfExists('event_third_parties');
//     \Illuminate\Support\Facades\Schema::dropIfExists('enquiries');
//     \Illuminate\Support\Facades\Schema::dropIfExists('charity_enquiries');
//     \Illuminate\Support\Facades\Schema::dropIfExists('event_enquiries');
//     \Illuminate\Support\Facades\Schema::dropIfExists('partner_enquiries');
//     \Illuminate\Support\Facades\Schema::dropIfExists('event_event_manager');
//     \Illuminate\Support\Facades\Schema::dropIfExists('event_managers');
//     \Illuminate\Support\Facades\Schema::dropIfExists('charity_partner_package');
//     \Illuminate\Support\Facades\Schema::dropIfExists('partner_packages');
//     \Illuminate\Support\Facades\Schema::dropIfExists('partners');
//     \Illuminate\Support\Facades\Schema::dropIfExists('participant_custom_fields');
//     \Illuminate\Support\Facades\Schema::dropIfExists('participants');
//     \Illuminate\Support\Facades\Schema::dropIfExists('event_custom_fields');
//     \Illuminate\Support\Facades\Schema::dropIfExists('setting_custom_fields');
//     echo "Existing tables dropped!". PHP_EOL;

//     $migrations = [
//         '2022_04_25_135535_create_participant_profiles',
//         '2022_04_28_084744_create_enquiries_table',
//         '2022_04_28_082928_create_charity_enquiries_table',
//         '2022_05_16_114946_create_event_managers_table',
//         '2022_05_16_114948_create_event_event_manager_table',
//         '2022_04_28_084735_create_partners_table',
//         '2022_04_28_084739_create_partner_channels_table',
//         '2022_04_28_184305_create_partner_packages_table',
//         '2022_04_28_184356_create_charity_partner_package_table',
//         '2022_05_05_150138_create_partner_enquiries_table',
//         '2022_05_16_114946_create_event_managers_table',
//         '2022_05_16_100758_create_event_custom_fields_table',
//         '2022_05_16_154948_create_event_enquiries_table',
//         '2022_08_01_145602_create_participants_table',
//         '2022_08_01_165704_create_participant_custom_fields_table',
//         '2022_08_25_114958_create_setting_custom_fields_table',
//     ];

//     foreach ($migrations as $key => $migration) {
//         \Illuminate\Support\Facades\Artisan::call("migrate --path=/database/migrations/$migration.php");
//         echo "Migration [$key] completed!". PHP_EOL;
//     }

//     // \Illuminate\Support\Facades\Artisan::call("migrate --path=/database/migrations/2022_04_28_084739_create_partner_channels_table.php");

//     // THIRD PARTY BEGIN
//     \Illuminate\Support\Facades\DB::table('migrations')
//         ->where('migration', '=', '2022_04_28_084741_create_event_third_parties_table')
//         ->delete();
//     echo "Mesmer Migration 2022_04_28_084741_create_event_third_parties_table removed!". PHP_EOL;

//     \Illuminate\Support\Facades\Artisan::call("migrate --path=/database/migrations/2022_04_28_084741_create_event_third_parties_table.php");
//     // THIRD PARTY END

//     // 2022_04_28_084742_create_event_category_event_third_party_table is needed by the external_enquiries table below
//     \Illuminate\Support\Facades\Artisan::call("migrate --path=/database/migrations/2022_04_28_084742_create_event_category_event_third_party_table.php");

//     // EXTERNAL ENQUIRIES BEGIN
//     \Illuminate\Support\Facades\DB::table('migrations')
//         ->where('migration', '=', '2022_04_28_084849_create_external_enquiries_table')
//         ->delete();
//     echo "Mesmer Migration 2022_04_28_084849_create_external_enquiries_table removed!". PHP_EOL;

//     \Illuminate\Support\Facades\Artisan::call("migrate --path=/database/migrations/2022_08_01_145802_create_external_enquiries_table.php");
//     // EXTERNAL ENQUIRIES END

//     // Run the migrations to edit the files
//     \Illuminate\Support\Facades\Artisan::call("migrate --path=/database/migrations/2023_01_17_160851_add_columns_to_events_table.php");
//     \Illuminate\Support\Facades\Artisan::call("migrate --path=/database/migrations/2023_01_17_161523_modify_columns_in_events_table.php");
//     \App\Modules\Event\Models\Event::where('reminder', 'none')->update(['reminder' => null]); // Change all records having the default value none to null

//     // Run the remaining migrations
//     \Illuminate\Support\Facades\Artisan::call("migrate");
// });

//Route::get('update-site-user-table', function () {
//    \Illuminate\Support\Facades\Schema::table('site_user', function (\Illuminate\Database\Schema\Blueprint $table) {
//        if (! \Illuminate\Support\Facades\Schema::hasColumn('site_user', 'status')) {
//            $table->string('status')
//                ->after('user_id')
//                ->default('active');
//
//            echo 1;
//        }
//    });
//});

Route::get('add-soft-delete-column/{table_name}', function (string $tableName) { // eg: add-soft-delete-column/roles
    \Illuminate\Support\Facades\Schema::table($tableName, function (\Illuminate\Database\Schema\Blueprint $table) use ($tableName) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'deleted_at')) {
            $table->softDeletes()->after('updated_at');

            echo 1;
        } else {
            echo "deleted_at column already exists";
        }
    });
});

Route::get('add-description-column-to-event-categories-table', function () {
    \Illuminate\Support\Facades\Schema::table('event_categories', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('event_categories', 'description')) {
            $table->mediumText('description')->nullable()
                ->after('slug');
            echo 1;
        } else {
            echo 'description column already exists!';
        }
    });
});

Route::get('add-unique-constraint-to-user_id-on-profiles-table', function () {
    \Illuminate\Support\Facades\Schema::table('profiles', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (\Illuminate\Support\Facades\Schema::hasColumn('profiles', 'user_id')) {
            $profiles = \DB::select("SELECT *, COUNT(*) FROM profiles GROUP BY user_id HAVING COUNT(*) > 1 ORDER BY created_at ASC");

            \Log::debug(json_encode($profiles));

            foreach ($profiles as $profile) {
                \Log::debug(json_encode($profile));

                \App\Modules\User\Models\Profile::where('user_id', $profile->user_id) // Delete all duplicate records except the parent/first/main record
                    ->where('id', '!=', $profile->id)
                    ->delete();
            }

            $table->foreignId('user_id')->unique()->change(); // Add a unique constraint to the user_id column

            echo 1;
        } else {
            echo "Please check the code!";
        }
    });
});

Route::get('add-unique-constraint-to-user_id-on-active_roles-table', function () {
    \Illuminate\Support\Facades\Schema::table('active_roles', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (\Illuminate\Support\Facades\Schema::hasColumn('active_roles', 'user_id')) {
            $activeRoles = \DB::select("SELECT *, COUNT(*) FROM active_roles GROUP BY user_id HAVING COUNT(*) > 1 ORDER BY created_at ASC");

            \Log::debug(json_encode($activeRoles));

            foreach ($activeRoles as $activeRole) {
                \Log::debug(json_encode($activeRole));

                \App\Modules\User\Models\ActiveRole::where('user_id', $activeRole->user_id) // Delete all duplicate records except the parent/first/main record
                    ->where('id', '!=', $activeRole->id)
                    ->delete();
            }

            $table->foreignId('user_id')->unique()->change(); // Add a unique constraint to the user_id column

            echo 1;
        }
    });
});

Route::get('make-description-column-nullable-on-combinations-table', function () {
    \Illuminate\Support\Facades\Schema::table('combinations', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (\Illuminate\Support\Facades\Schema::hasColumn('combinations', 'description')) {
            $table->string('description')->nullable()->change();

            echo 1;
        } else {
            echo "description column not found!";
        }
    });
});

Route::get('add-robots-column-on-meta-table', function () {
    \Illuminate\Support\Facades\Schema::table('meta', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('meta', 'robots')) {
            $table->string('robots', 255)->nullable()->after('keywords');

            echo 1;
        } else {
            echo "robots column already exists!";
        }
    });
});

Route::get('remove-lat-and-lng-columns-from-locations-table', function () {
    try {
        if (\Illuminate\Support\Facades\Schema::hasColumn('locations', 'latitude')) {
            Location::whereNotNull('latitude')->whereNotNull('longitude')->each(function (Location $location) {
                $location->update([
                    'coordinates' => new Point($location->latitude, $location->longitude, 4326)
                ]);
            });

            \Illuminate\Support\Facades\Schema::table('locations', function (\Illuminate\Database\Schema\Blueprint $table) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('locations', 'latitude')) {
                    $table->dropColumn('latitude');
                    $table->dropColumn('longitude');
                    echo 1;
                }
            });
        } else {
            echo "The column does not exists";
        }
    } catch (\Exception $e) {
        echo $e->getMessage();
    };
});

Route::get('add-path-column-unique-on-combinations-table', function () {
    \Illuminate\Support\Facades\Schema::table('combinations', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('combinations', 'path')) {
            $table->string('path')->nullable()->after('description');

            echo 1;
        } else {
            echo "The column already exists";
        }
    });
});

Route::get('update-upload-url', function () {
    \App\Models\Upload::chunk(500, function ($uploads) {
        foreach ($uploads as $upload) {
            $url = $upload->url;

            if (\Illuminate\Support\Str::startsWith($url, 'uploads/media/images')) {
                $url = \Illuminate\Support\Str::replace('uploads/media/images', 'uploads/public/media/images', $url);
            } else if (\Illuminate\Support\Str::startsWith($url, '/uploads/media/images')) {
                $url = \Illuminate\Support\Str::replace('/uploads/media/images', 'uploads/public/media/images', $url);
            } else if (\Illuminate\Support\Str::startsWith($url, '/uploads/documents')) {
                $url = \Illuminate\Support\Str::replace('/uploads/documents', 'uploads/private/documents', $url);
            } else if (\Illuminate\Support\Str::startsWith($url, 'uploads/documents')) {
                $url = \Illuminate\Support\Str::replace('uploads/documents', 'uploads/private/documents', $url);
            }

            if (\Illuminate\Support\Str::contains($url, '//')) {
                $url = \Illuminate\Support\Str::replace('//', '/', $url);
            }

            $upload->update([
                'url' => $url
            ]);
        }
    });

    echo "done";
});

Route::get('add-soft-delete-and-soft-delete-column-to-redirects-table', function () {
    \Illuminate\Support\Facades\Schema::table('redirects', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (\Illuminate\Support\Facades\Schema::hasColumn('redirects', 'status')) {
            $table->dropColumn('status');
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('redirects', 'soft_delete')) {
            $table->string('soft_delete')->nullable()->after('redirect_url');
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('redirects', 'hard_delete')) {
            $table->string('hard_delete')->nullable()->after('soft_delete');
        }
    });

    echo 1;
});

Route::get('add-nullable-constraint-to-target-url-column-on-redirects-table', function () {
    \Illuminate\Support\Facades\Schema::table('redirects', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (\Illuminate\Support\Facades\Schema::hasColumn('redirects', 'redirect_url')) {
            $table->string('redirect_url')->nullable()->change();
        }
    });

    echo 1;
});

Route::get('add-site-id-column-to-active-roles-table/{site_id}', function (int $siteId) {
    \Illuminate\Support\Facades\Schema::table('active_roles', function (\Illuminate\Database\Schema\Blueprint $table) use ($siteId) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('active_roles', 'site_id')) {
            $table->foreignId('site_id')
                ->default($siteId)
                ->after('role_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->dropUnique(['user_id']);
            $table->unique(['user_id', 'role_id', 'site_id']);
        } else {
            echo "Run already!";
            // $table->dropUnique(['user_id']);
        }
    });

    echo 1;
});

Route::get('set-default-values-for-state-column-to-records-on-invoices-table', function () {
    \DB::table('invoices')->update(['state' => \App\Enums\InvoiceStateEnum::Complete]);

    echo "done";
});

Route::get('set-default-values-for-status-column-to-records-on-invoice-items-table', function () {
    \DB::table('invoice_items')->update(['status' => \App\Enums\InvoiceItemStatusEnum::Paid]);

    echo "done";
});

Route::get('change_invoice_item_type_from_participant_transfer_to_participant_transfer_new_event', function () {
    \DB::table('invoice_items')->where('type', 'participant_transfer')->update(['type' => \App\Enums\InvoiceItemTypeEnum::ParticipantTransferNewEvent]);

    echo "done";
});

Route::get('update-fees-on-event-event-category-table', function () {
    \App\Modules\Event\Models\EventEventCategory::chunk(500, function ($eecs) {
        foreach ($eecs as $eec) {
            $data = [];

            if ($eec->local_fee == 0) {
                $data['local_fee'] = null;
            }

            if ($eec->international_fee == 0) {
                $data['international_fee'] = null;
            }

            if ($eec->total_places == 0) {
                $data['total_places'] = null;
            }

            if ($eec->classic_membership_places == 0) {
                $data['classic_membership_places'] = null;
            }

            if ($eec->premium_membership_places == 0) {
                $data['premium_membership_places'] = null;
            }

            if ($eec->two_year_membership_places == 0) {
                $data['two_year_membership_places'] = null;
            }

            if (!empty($data)) {
                $eec->update($data);
            }
        }
    });

    echo "done";
});

Route::get('update-null-fees-values-to-0-on-event-event-category-table', function () {
    \App\Modules\Event\Models\EventEventCategory::chunk(500, function ($eecs) {
        foreach ($eecs as $eec) {
            $data = [];

            if ($eec->local_fee != null && $eec->international_fee == null) {
                $data['international_fee'] = $eec->local_fee;
            }

            if ($eec->international_fee != null && $eec->local_fee == null) {
                $data['local_fee'] = $eec->international_fee;
            }

            if ($eec->local_fee == null) {
                $data['local_fee'] = 0;
            }

            if ($eec->international_fee == null) {
                $data['international_fee'] = 0;
            }

            if (!empty($data)) {
                $eec->update($data);
            }
        }
    });

    echo "done";
});

Route::get('add-site-id-and-type-column-to-verification-codes-table/{site_id}', function (int $siteId) {
    \Illuminate\Support\Facades\Schema::table('verification_codes', function (\Illuminate\Database\Schema\Blueprint $table) use ($siteId) {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('verification_codes', 'site_id')) {
            $table->foreignId('site_id')
                ->default($siteId)
                ->after('user_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['user_id', 'site_id', 'code']);
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('verification_codes', 'type')) {
            $table->string('type')
                ->nullable()
                ->after('code');
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('verification_codes', 'is_active')) {
            $table->boolean('is_active')
                ->default(true)
                ->after('code');
        }
    });

    echo 'done';
});

Route::get('create-site-settings-for-runthrough-in-production', function () {
    try {
        $site = Site::where('code', SiteCodeEnum::RunThrough)->first();

        if ($site) {
            $setting = $site->setting()->updateOrCreate([]);

            if ($setting->settingCustomFields()->where('key', \App\Enums\SettingCustomFieldKeyEnum::ParticipantTransferFee)->doesntExist()) {
                $setting->settingCustomFields()->create([
                    'key' => \App\Enums\SettingCustomFieldKeyEnum::ParticipantTransferFee,
                    'value' => 5
                ]);
            }
        }

        echo "done";
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
});

Route::get('set-default-event-registration-method-for-existing-records', function () {
    \App\Modules\Event\Models\Event::with('eventThirdParties')
        ->withDrafted()
        ->withTrashed()
        ->chunk(500, function ($events) {
            foreach ($events as $event) {
                $registrationMethod = [];

                if (count($event->eventThirdParties)) {
                    $registrationMethod['website_registration_method'] = \App\Modules\Event\Enums\EventRegistrationMethodTypesEnum::External;
                    $registrationMethod['portal_registration_method'] = \App\Modules\Event\Enums\EventRegistrationMethodTypesEnum::Internal;
                } else {
                    $registrationMethod['website_registration_method'] = \App\Modules\Event\Enums\EventRegistrationMethodTypesEnum::Internal;
                    $registrationMethod['portal_registration_method'] = \App\Modules\Event\Enums\EventRegistrationMethodTypesEnum::Internal;
                }

                $event->update(['registration_method' => $registrationMethod]);
            }
        });
    });

Route::get('create-organisations-and-assign-sites-to-them', function () {
    if (\Illuminate\Support\Facades\Schema::hasTable('organisations')) {
        foreach (OrganisationEnum::cases() as $organisation) {
            \App\Modules\Setting\Models\Organisation::updateOrCreate([
                'domain' => $organisation->value,
            ],
            [
                'name' => $organisation->name,
                'code' => collect(OrganisationCodeEnum::cases())->filter(function ($code) use ($organisation) {
                    return $code->name == $organisation->name;
                })->first()?->value ?? $organisation->value
            ]);
        }
    }

    \App\Modules\Setting\Models\Site::chunk(100, function ($sites) {
        foreach ($sites as $site) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('sites', 'organisation_id')) {
                if (SiteEnum::tryFrom($site->domain)) {
                    if (SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive, $site)) {
                        $site->update(['organisation_id' => \App\Modules\Setting\Models\Organisation::where('domain', OrganisationEnum::GWActive->value)->value('id')]);
                    } else if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency, $site)) {
                        $site->update(['organisation_id' => \App\Modules\Setting\Models\Organisation::where('domain', OrganisationEnum::SportsMediaAgency->value)->value('id')]);
                    }
                }
            }
        }
    });

    echo "done";
});

Route::get('update-status-values-on-ongoing-external-transactions-table', function () {
    \App\Modules\Finance\Models\OngoingExternalTransaction::chunk(500, function ($transactions) {
        \DB::table('ongoing_external_transactions')->where('status', 'completed')->update(['status' => \App\Modules\Finance\Enums\OngoingExternalTransactionStatusEnum::Successful->value]);
    });

    echo "done";
});

Route::fallback(function () { // this route will be executed when no other route matches the incoming request.
    return response()->json([
        'status' => false,
        'message' => 'The resource was not found!',
        'errors' => null
    ], 404);
})->name('web.fallback.404');
