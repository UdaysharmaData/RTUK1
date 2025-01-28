<?php

namespace App\Http\Controllers\Portal;

use App\Enums\SettingCustomFieldTypeEnum;
use Str;
use Rule;
use Validator;
use App\Traits\Response;
use App\Traits\SiteTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Enums\SocialPlatformEnum;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use App\Modules\Setting\Resources\SettingResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Models\Dashboard;
use App\Models\VirtualSetting;
use App\Modules\Setting\Models\Site;
use App\Modules\Setting\Models\Setting;

/**
 * @group Settings
 * Manages the application settings
 * @authenticated
 */
class SettingController extends Controller
{
    use Response, SiteTrait;

    /*
    |--------------------------------------------------------------------------
    | Setting Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the application settings.
    |
    */

    public function __construct()
    {
        parent::__construct();

        $this->middleware('role:can_manage_settings');
    }

    /**
     * Get the site's setting details.
     * 
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        try {
            $setting = Setting::with(['site', 'settingCustomFields'])
                ->whereHas('site', function($query) {
                    $query->where('id', static::getSite()?->id);
                })->firstOrFail();

        } catch (ModelNotFoundException $e) {

            return $this->error('The setting was not found!', 406);
        }

        return $this->success('The setting details', 200, new SettingResource($setting));
    }

    /**
     * Get the site's socials
     * 
     * @return JsonResponse
     */
    public function socials(): JsonResponse
    {
        try {
            $setting = Setting::with(['site', 'socials'])
                ->whereHas('site', function($query) {
                    $query->where('id', static::getSite()?->id);
                })->firstOrFail();

        } catch (ModelNotFoundException $e) {

            return $this->error('The setting was not found!', 406);
        }

        return $this->success(Str::ucfirst(Str::lower($setting->site->name)).' socials', 200, new SettingResource($setting));
    }

    /**
     * Update a site's socials
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSocials(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'socials' => ['required'],
            'socials.*.platform' => [
                'distinct',
                new Enum(SocialPlatformEnum::class),
                Rule::requiredIf($request->socials == true)
            ],
            'socials.*.url' => [
                'active_url',
                'distinct',
                Rule::requiredIf($request->socials == true)
            ],
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 400,  $validator->errors()->messages());
        }

        try {
            $setting = Setting::with(['site', 'socials'])
                ->whereHas('site', function($query) {
                    $query->where('id', static::getSite()?->id);
                })->firstOrFail();

            try {

                if ($request->filled('socials') && $request->socials && $request->socials[0]['platform']) { // Update the sites's socials
                    foreach ($request->socials as $social) {
                        $setting->socials()->updateOrCreate([
                            'platform' => $social['platform'],
                        ], [
                            'url' => $social['url']
                        ]);
                    }
                }

            } catch (QueryException $e) {

                return $this->error('Unable to update the socials! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error(static::getSite()->name .' setting was not found!', 406);
        }

        return $this->success('Successfully updated the socials', 200, new SettingResource($setting->load('socials')));
    }

    /**
     * Delete a site's social
     * 
     * @urlParam id int required The id of the social. Example: 1423
     * @return JsonResponse
     */
    public function destroySocial(int $id): JsonResponse
    {
        try {
            $setting = Setting::with(['site', 'socials'])
                ->whereHas('site', function($query) {
                    $query->where('id', static::getSite()?->id);
                })->firstOrFail();

            try {
                $social = $setting->socials()->where('id', $id)->firstOrFail();

                try {

                    $social->delete();

                } catch (QueryException $e) {

                    return $this->error('Unable to delete the socials! Please try again.', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {

                return $this->error('The social was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error(static::getSite()->name .' setting was not found!', 404);
        }

        return $this->success('Successfully deleted the socials', 200, $social);
    }

    /**
     * Update the custom fields of a site's setting
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCustomFields(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'custom' => ['required'],
            'custom.*.key' => [
                'string',
                Rule::requiredIf($request->custom == true)
            ],
            'custom.*.value' => [
                // 'string',
                Rule::requiredIf($request->custom == true)
            ],
            // Must be one of [per_event, all_events, none]
            'custom.*.type' => [
                new Enum(SettingCustomFieldTypeEnum::class),
                Rule::requiredIf($request->custom == true)
            ],
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 400,  $validator->errors()->messages());
        }

        try {
            $setting = Setting::with(['site', 'settingCustomFields'])
                ->whereHas('site', function($query) {
                    $query->where('id', static::getSite()?->id);
                })->firstOrFail();

            try {

                if ($request->filled('custom') && $request->custom && $request->custom[0]['key']) { // Update the custom fields of a sites's setting
                    foreach ($request->custom as $field) {
                        $setting->settingCustomFields()->updateOrCreate([
                            'key' => $field['key'],
                        ], [
                            'value' => $field['value'],
                            'type'  => $field['type']
                        ]);
                    }
                }

            } catch (QueryException $e) {

                return $this->error('Unable to update the custom fields! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {

            return $this->error(static::getSite()->name .' setting was not found!', 406);
        }

        return $this->success('Successfully updated the custom fields', 200, new SettingResource($setting->load('settingCustomFields')));
    }

    /**
     * Delete a custom fields of a site's setting
     * 
     * @urlParam id int required The id of the social. Example: 56
     * @return JsonResponse
     */
    public function destroyCustomField(int $id): JsonResponse
    {
        try {
            $setting = Setting::with(['site', 'settingCustomFields'])
                ->whereHas('site', function($query) {
                    $query->where('id', static::getSite()?->id);
                })->firstOrFail();

            try {
                $customField = $setting->settingCustomFields()->where('id', $id)->firstOrFail();

                try {

                    $customField->delete();

                } catch (QueryException $e) {

                    return $this->error('Unable to delete the custom field! Please try again.', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {

                return $this->error('The custom field was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error(static::getSite()->name .' setting was not found!', 404);
        }

        return $this->success('Successfully delete the custom fields', 200, $customField);
    }

    // /**
    //  * Get roles dashboards
    //  * 
    //  * @return JsonResponse
    //  */
    // public function dashboards()
    // {
    //     return $this->success('The list of roles dashboards', 200, Dashboard::with('role')->get());
    // }

    // /**
    //  * Get a role's dashboard (items displayed)
    //  * 
    //  * @urlParam id integer required The role's id. Example: 6
    //  * @return JsonResponse
    //  */
    // public function dashboard(int $id): JsonResponse
    // {
    //     try {
    //         $dashboard = Dashboard::where('role_id', $id)->firstOrFail();
    //         if ($dashboard->widgets) {
    //             $dashboard['widgets'] = explode(',', $dashboard['widgets']);
    //         }
    //     } catch (ModelNotFoundException $e) {
    //         return $this->error('The role\'s dashboard was not found!', 404);
    //     }

    //     return $this->success($dashboard->role->name.' dashboard', 200, $dashboard);
    // }

    // /**
    //  * Update a role's dashboard (items displayed)
    //  * 
    //  * @param Request $request
    //  * @urlParam id integer required The role's id. Example: 6
    //  * @return JsonResponse
    //  */
    // public function updateDashboard(Request $request, int $id): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'widgets' => ['required', 'array'],
    //         'news_title' => ['required', 'string'],
    //         'news_body' => ['required', 'string'],
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
    //     }

    //     try {
    //         $dashboard = Dashboard::where('role_id', $id)->firstOrFail();
    //         try {
    //             if ($request->widgets) {
    //                 $request['widgets'] = implode(',', $request->widgets);
    //             }

    //             $dashboard->fill($request->all());
    //             $dashboard->role_id = $id;
    //             $dashboard->save();
    //         } catch (QueryException $e) {
    //             return $this->error('Unable to update the role\'s dashboard! Please try again.', 406);
    //         }
    //     } catch (ModelNotFoundException $e) {
    //         return $this->error('The role\'s dashboard was not found!', 404);
    //     }

    //     $dashboard->load('role');

    //     return $this->success('Successfully updated the '.Str::lower($dashboard->role->name).' dashboard', 200, $dashboard);
    // }

    // /**
    //  * Get events statistics to display on a role's dashboard
    //  * 
    //  * @return JsonResponse
    //  */
    // public function events(): JsonResponse
    // {
    //     try {
    //         $settings = Setting::with('site')
    //             ->select('site_id', 'classic_membership_default_places', 'premium_membership_default_places', 'classic_renewal', 'new_classic_renewal', 'premium_renewal', 
    //                 'new_premium_renewal', 'two_year_renewal', 'new_two_year_renewal', 'partner_renewal')
    //             ->whereHas('site', function($query) {
    //                 $query->where('id', static::getSite()?->id);
    //             })->firstOrFail();
    //     } catch (ModelNotFoundException $e) {
    //         return $this->error(static::getSite()?->name.' setting was not found!', 406);
    //     }

    //     return $this->success('The events statistics to display on the dashboard!', 200, $settings);
    // }

    // /**
    //  * Update events statistics displayed on a role's dashboard
    //  * 
    //  * @param Request $request
    //  * @return JsonResponse
    //  */
    // public function updateEvents(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'classic_membership_default_places' => ['required', 'integer'],
    //         'premium_membership_default_places' => ['required', 'integer'],
    //         'classic_renewal' => ['required', 'integer'],
    //         'new_classic_renewal' => ['required', 'numeric'],
    //         'premium_renewal' => ['required', 'integer'],
    //         'new_premium_renewal' => ['required', 'integer'],
    //         'two_year_renewal' => ['required', 'numeric'],
    //         'new_two_year_renewal' => ['required', 'numeric'],
    //         'partner_renewal' => ['required', 'numeric'],
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error('Please resolve the warnings!', 400,  $validator->errors()->messages());
    //     }

    //     try {
    //         $settings = Setting::with('site')
    //             ->select('site_id', 'classic_membership_default_places', 'premium_membership_default_places', 'classic_renewal', 'new_classic_renewal', 'premium_renewal', 
    //                 'new_premium_renewal', 'two_year_renewal', 'new_two_year_renewal', 'partner_renewal')
    //             ->whereHas('site', function($query) {
    //                 $query->where('id', static::getSite()?->id);
    //             })->firstOrFail();

    //         try {
    //             $settings->fill($request->all());
    //             $settings->site_id = Site::where('id', static::getSite()?->id)->first()->id;
    //             $settings->save();
    //         } catch (QueryException $e) {
    //             return $this->error('Unable to update the dashboard event statistics! Please try again.', 406);
    //         }
    //     } catch (ModelNotFoundException $e) {
    //         return $this->error(static::getSite()?->name.' setting was not found!', 406);
    //     }

    //     return $this->success('Successfully updated the dashboard event statistics', 200, $settings);
    // }

    // /**
    //  * Get VMM (virtual marathon series) settings
    //  * 
    //  * @return JsonResponse
    //  */
    // public function vmm(): JsonResponse
    // {
    //     try {
    //         $settings = VirtualSetting::firstOrFail();
    //     } catch (ModelNotFoundException $e) {
    //         return $this->error('The vms settings was not found!', 406);
    //     }

    //     return $this->success('The vms settings!', 200, $settings);
    // }

    // /**
    //  * Update VMM (virtual marathon series) settings
    //  * 
    //  * @param Request $request
    //  * @return JsonResponse
    //  */
    // public function updateVmm(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'fundraising_setup_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg|max:2048'],
    //         // Example: https://www.justgiving.com/fundraising/tips/how-to-create-a-fundraising-page
    //         'fundraising_setup_url' => ['nullable', 'url'],
    //         'fundraising_ideas_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg|max:2048'],
    //         // Example: https://runforcharity.com/fundraising/fundraising-ideas-237
    //         'fundraising_ideas_url' => ['nullable', 'url'],
    //         // Example: <ol><li><h2>Dave Lanchard - &pound;4565</h2></li><li><h2>Michelle Roy - &pound;4020</h2></li><li><h2>Laura Richardson - &pound;2500</h2></li>
    //         //        <li><h2>James Ashby - &pound;2228</h2></li><li><h2>Jeremy Gubbins - &pound;2050</h2></li></ol>
    //         'top_fundraisers' => ['nullable', 'string'],
    //         'faqs' => ['nullable', 'string']
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error('Please resolve the warnings!', 400,  $validator->errors()->messages());
    //     }

    //     $settings = VirtualSetting::first();

    //     if (!$settings) {
    //         $settings = new VirtualSetting();
    //     }

    //     try {
    //         if ($request->file('fundraising_setup_image') && $request->file('fundraising_setup_image')->isValid()) { // Upload fundraising setup image
    //             if (isset($settings->fundraising_setup_image)) {
    //                 if (Storage::disk(config('filesystems.default'))->exists($settings->fundraising_setup_image)) { // Delete the existing image if it exists
    //                     unlink(storage_path('app/public'.$settings->fundraising_setup_image));
    //                 }
    //             }
    
    //             $fname = Str::random(40).'.'.$request->file('fundraising_setup_image')->getClientOriginalExtension();
    //             $request->fundraising_setup_image->move(storage_path('app/public'.config('app.images_path')), $fname);
    //             $settings->fundraising_setup_image = config('app.images_path').$fname;
    //         }

    //         if ($request->file('fundraising_ideas_image') && $request->file('fundraising_ideas_image')->isValid()) { // Upload fundraising ideas image
    //             if (isset($settings->fundraising_ideas_image)) {
    //                 if (Storage::disk(config('filesystems.default'))->exists($settings->fundraising_ideas_image)) { // Delete the existing image if it exists
    //                     unlink(storage_path('app/public'.$settings->fundraising_ideas_image));
    //                 }
    //             }
    
    //             $fname = Str::random(40).'.'.$request->file('fundraising_ideas_image')->getClientOriginalExtension();
    //             $request->fundraising_ideas_image->move(storage_path('app/public'.config('app.images_path')), $fname);
    //             $settings->fundraising_ideas_image = config('app.images_path').$fname;
    //         }

    //         $settings->top_fundraisers = $request->top_fundraisers ?? null;
    //         $settings->faqs = $request->faqs ?? null;
    //         $settings->fundraising_setup_url = $request->fundraising_setup_url ?? null;
    //         $settings->fundraising_ideas_url = $request->fundraising_ideas_url ?? null;
    //         $settings->save();
    //     } catch (QueryException $e) {
    //         return $this->error('Unable to update the vms settings! Please try again.', 406);
    //     }
    //     return $this->success('Successfully updated the vms settings!', 200, $settings);
    // }
}
