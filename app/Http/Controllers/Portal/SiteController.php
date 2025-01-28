<?php

namespace App\Http\Controllers\Portal;

use DB;
use Rule;
use Validator;
use App\Traits\Response;
use App\Traits\SiteTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Enums\SocialPlatformEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\SiteResource;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use App\Enums\SettingCustomFieldTypeEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Modules\Setting\Models\Site;
use App\Modules\Setting\SettingCustomField;

/**
 * @group Sites
 * Manages sites on the application
 * @authenticated
 */
class SiteController extends Controller
{
    use SiteTrait;

    /*
    |--------------------------------------------------------------------------
    | Site Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the different sites on the application.
    |
    */

    use Response;

    public function __construct()
    {
        parent::__construct();

        $this->middleware('role:can_manage_sites');
    }

    /**
     * Paginated sites for dropdown fields.
     * 
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     */
    public function all(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $sites = DB::table('sites')
            ->select('ref', 'name', 'domain', 'code');

        if ($request->filled('term')) {
            $sites = $sites->where('name', 'like', "%{$request->term}%");
        }

        if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
            $sites = $sites->where('id', static::getSite()?->id);
        }

        $sites = $sites->orderBy('name');

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $sites = $sites->paginate($perPage);

        return $this->success('All sites', 200, [
            'sites' => new SiteResource($sites)
        ]);
    }

    /**
     * The list of sites
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $sites = Site::all();

        return $this->success('The list of sites', 200, [
            'sites' => new SiteResource($sites)
        ]);
    }

    /**
     * Create a new site
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Create a site', 200);
    }

    /**
     * Store the new site
     * The site's settings gets created too.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The domain name of the site. Example: runforcharity.com
            'domain' => ['required', 'string', "regex:/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/"],
            // The name of the site. Example: Run For Charity
            'name' => ['required', 'string', 'max:60'],
            // The status of the site. Example: false
            'status' => ['required', 'boolean'],
            'socials' => [
                'nullable',
            ],
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
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            // The domain must be unique
            $site = Site::where('domain', $request->domain)->first();

            if ($site) {
                throw new ModelNotFoundException('The site already exists');
            }

            try {
                DB::beginTransaction();

                $site = new Site();
                $site->domain = $request->domain;
                $site->name = $request->name;
                $site->status = $request->status;
                $site->key = Site::generateRandomString();
                $site->save();

                // Create and set the default site settings
                $setting = $site->setting()->create();

                $setting->settingCustomFields()->create([
                    'key' => 'classic_membership_default_places',
                    'value' => 5,
                    'type' => SettingCustomFieldTypeEnum::PerEvent
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'premium_membership_default_places',
                    'value' => 20,
                    'type' => SettingCustomFieldTypeEnum::PerEvent
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'two_year_membership_default_places',
                    'value' => 20,
                    'type' => SettingCustomFieldTypeEnum::PerEvent
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'partner_membership_default_places',
                    'value' => 1,
                    'type' => SettingCustomFieldTypeEnum::AllEvents
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'classic_renewal',
                    'value' => 500
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'new_classic_renewal',
                    'value' => 750
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'premium_renewal',
                    'value' => 1000
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'new_premium_renewal',
                    'value' => 1200
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'two_year_renewal',
                    'value' => 1800
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'new_two_year_renewal',
                    'value' => 2500
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'partner_renewal',
                    'value' => 0
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'new_partner_renewal',
                    'value' => 0
                ]);

                $setting->settingCustomFields()->create([
                    'key' => 'participant_transfer_fee',
                    'value' => 10
                ]);

                if ($request->socials && $request->socials[0]['platform']) { // Update the site's socials
                    foreach ($request->socials as $social) {
                        $setting->socials()->updateOrCreate([
                            'platform' => $social['platform'],
                        ], [
                            'url' => $social['url']
                        ]);
                    }
                }

                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to create the site! Please try again', 406);
            }
        } catch(ModelNotFoundException $e) {
            return $this->error($e->getMessage(), 409);
        }

        return $this->success('Successfully created the site', 201, new SiteResource($site->load(['setting.settingCustomFields', 'setting.socials'])));
    }

    /**
     * Get a site
     *
     * @urlParam id int required The id of the site. Example: 2
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $site = Site::findOrFail($id);

        } catch (ModelNotFoundException $e) {

            return $this->error('The site was not found!', 404);
        }

        return $this->success('The site details', 200, new SiteResource($site));
    }

    /**
     * Edit a site
     *
     * @urlParam id int required The id of the site. Example: 2
     * @return JsonResponse
     */
    public function edit(int $id): JsonResponse
    {
        try {
            $site = Site::findOrFail($id);

        } catch (ModelNotFoundException $e) {

            return $this->error('The site was not found!', 404);
        }

        return $this->success('Edit the site', 200, new SiteResource($site));
    }

    /**
     * Update a site
     *
     * @param  Request  $request
     * @urlParam id int required The id of the site. Example: 2
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The domain name of the site. Example: runforcharity.com
            'domain' => ['required', 'string', "regex:/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/"],
            // The name of the site. Example: Run For Charity
            'name' => ['required', 'string', 'max:60'],
            // The status of the site. Example: false
            'status' => ['required', 'boolean']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $site = Site::findOrFail($id);

            try {
                $site->update($request->all());

            } catch(QueryException $e) {

                return $this->error('Unable to update the site! Please try again.', 406);
            }
        } catch(ModelNotFoundException $e) {

            return $this->error('The site was not found!', 404);
        }

        return $this->success('Successfully updated the site', 200, new SiteResource($site));
    }

    /**
     * Delete a site
     * The site's settings gets deleted too.
     * 
     * @urlParam id int required The id of the site. Example: 4
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $site = Site::findOrFail($id);

            try {
                DB::beginTransaction();

                if ($site->setting) {
                    $site->setting->delete();
                }

                $site->delete();
                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();

                return $this->error('Unable to delete the site! Please try again.', 406);
            }
        } catch(ModelNotFoundException $e) {

            return $this->error('The site was not found!', 404);
        }

        return $this->success('Successfully deleted the site', 200, $site);
    }
}

