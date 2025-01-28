<?php

namespace App\Modules\Charity\Controllers;

use DB;
use Str;
use Auth;
use Rule;
use Excel;
use Storage;
use Validator;
use Carbon\Carbon;
use App\Mail\Mail;
use App\Traits\Response;
use Illuminate\Http\Request;
use App\Http\Helpers\Colour;
use App\Http\Helpers\AccountType;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Enums\RoleNameEnum;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Enums\ContractStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\LocationUseAsEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\CharityEventTypeEnum;
use App\Mail\CharityAccountCreatedMail;
use App\Enums\CharityMembershipTypeEnum;
use App\Enums\MetaRobotsEnum;
use App\Jobs\SendMembershipRenewalInvoiceJob;

use App\Modules\Event\Resources\EventResource;
use App\Modules\Charity\Resources\CharityResource;
use App\Modules\Charity\Resources\CharityCategoryResource;

use App\Http\Requests\InvoiceCreateRequest;
use App\Modules\Charity\Requests\CharityCreateRequest;
use App\Modules\Charity\Requests\CharityCallNoteRequest;
use App\Modules\Charity\Requests\CharityMembershipRequest;
use App\Modules\Charity\Requests\CharityUpdateProfileRequest;
use App\Modules\Charity\Requests\CharityUpdateContentRequest;

use App\Http\Helpers\TextHelper;
use App\Exports\CharityCsvExport;

use App\Models\Upload;
use App\Models\Invoice;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CharityProfile;
use App\Modules\Charity\Models\CharityCategory;

use App\Modules\User\Models\Role;
use App\Models\CallNote;
use App\Models\CharityPlace;
use App\Modules\Charity\Models\CharityEvent;
use App\Modules\Charity\Models\CharityMembership;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Traits\SiteTrait;
use App\Traits\HelperTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;

/**
 * @group Charities
 * Manages charities on the application
 * @authenticated
 */
class CharityController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Charity Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with charities. That is
    | the creation, view, update, delete and more ...
    |
    */

    use Response, SiteTrait, HelperTrait, UploadTrait, DownloadTrait, UploadModelTrait;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('role:can_manage_charities', [
            'except' => [
                'all',
            ],
            // 'only' => [
            //     'index',
            //     'create',
            //     'delete',
            //     'destroyPermanently',
            //     'updateMembership',
            //     'toggleFundraisingEmailIntegration',
            //     'toggleCharityCheckoutIntegration',
            //     'callNotes',
            //     'createCallNote',
            //     'updateCallNote',
            //     'deleteCallNote',
            //     'export',
            //     'invoices',
            //     'createInvoice',
            //     'postCreateInvoice',
            //     'deleteInvoice'
            // ]
        ]);
    }

    /**
     * Paginated charities for dropdown fields.
     * 
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1 
     * @queryParam per_page integer Items per page No-example
     */
    public function all(Request $request): JsonResponse
    {
        $charities = DB::table('charities')
            ->whereNull('deleted_at')
            ->select('charities.id', 'charities.ref', 'charities.name', 'charities.slug')
            ->join('charity_memberships', 'charities.id', '=', 'charity_memberships.charity_id')
            ->where('charity_memberships.status', Charity::ACTIVE)
            ->where('charities.status', Charity::ACTIVE)
            ->orderBy('name', 'ASC');

        if (AccountType::isAccountManagerOrCharityOwnerOrCharityUser()) {
            $charities = $charities->join('charity_user', 'charity_user.charity_id', '=', 'charities.id')
                ->where('charity_user.user_id', Auth::user()->id)
                ->where(function ($query) {
                    $query->where('charity_user.type', CharityUserTypeEnum::Owner);
                    $query->orWhere('charity_user.type', CharityUserTypeEnum::User);
                    $query->orWhere('charity_user.type', CharityUserTypeEnum::Manager);
                });
        }

        if ($request->filled('term')) {
            $charities = $charities->where('name', 'like', "%{$request->term}%");
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;

        $charities = $charities->paginate($perPage);

        return $this->success('All charities', 200, new CharityResource($charities));
    }

    /**
     * The list of charities
     *
     * @queryParam membership_type string Filter by membership type. Must be one of premium, classic, partner, two_year. Example: premium
     * @queryParam status bool Filter by status. Example 1
     * @queryParam category string Filter by charity category slug. Example: cancer-children-youth
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'category' => ['sometimes', 'nullable', Rule::exists('charity_categories', 'slug')],
            'membership_type' => ['sometimes', 'nullable', new Enum(CharityMembershipTypeEnum::class)],
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charities = Charity::with(['charityManager.user', 'charityCategory', 'logo']);

        if (AccountType::isAccountManager()) {
            $charities = $charities->whereHas('charityManager', function ($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        if ($request->filled('status')) {
            $charities = $charities->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $charities = $charities->whereHas('charityCategory', function ($query) use ($request) {
                $query->where('slug', $request->category);
            });
        }

        if ($request->filled('term')) {
            $charities = $charities->where('name', 'LIKE', '%'.$request->term.'%');
        }

        if ($request->filled('membership_type')) {
            $charities = $charities->whereHas('latestCharityMembership', function ($query) use ($request) {
                $query->where('type', $request->membership_type);
            });
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $charities = $charities->paginate($perPage);

        return $this->success('The list of charities', 200, [
            'charities' => new CharityResource($charities),
            'membership_types' => CharityMembershipTypeEnum::_options()
        ]);
    }

    /**
     * The list of charities
     *
     * @group Charity - Client
     * @unauthenticated
     * 
     * @queryParam status bool Filter by status. Example 1
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function _index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charities = Charity::with(['charityCategory', 'logo']);

        if ($request->filled('status') && $request->status) { // Filter by active membership
            $charities = $charities->where('status', Charity::ACTIVE)
                ->whereHas('latestCharityMembership', function ($query) use ($request) {
                    $query->whereDate('expiry_date', '>', Carbon::now());
                });
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $charities = $charities->paginate($perPage);

        return $this->success('The list of charities', 200, [
            'charities' => new CharityResource($charities)
        ]);
    }

    /**
     * Create a charity
     *
     * @return  JsonResponse
     */
    public function create(): JsonResponse
    {
        $users = User::select('id', 'ref', 'first_name', 'last_name')->whereHas('roles', function ($query) {
            $query->where('name', RoleNameEnum::AccountManager);
        })->get();

        return $this->success('Create a charity', 200, [
            'account_managers' => $users,
            'categories' => new CharityCategoryResource(CharityCategory::all()),
            'robots' => MetaRobotsEnum::_options()
        ]);
    }

    /**
     * Store a charity
     *
     * @param  CharityCreateRequest $request
     * @return JsonResponse
     */
    public function store(CharityCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $charity = new Charity();
            $charity->fill($request->all());
            $charity->status = Charity::ACTIVE;
            $charity->charity_checkout_integration = 0; // ?? charity_checkout_integration default value is 1 on the schema. Shouldn't it be changed to 0 ?
            $charity->email = $request->support_email;
            $charity->charity_category_id = CharityCategory::where('slug', $request->category)->first()?->id;

            $charity->name = $request->name;

            if ($request->filled('primary_color')) {
                $charity->primary_color = Colour::pantone2hex($request->primary_color);
            }

            if ($request->filled('secondary_color')) {
                $charity->secondary_color = Colour::pantone2hex($request->secondary_color);
            }

            $charity->save();

            $charity->location()->createOrUpdate([], [ // Create the charity address
                'address' => $request->address,
                'use_as' => LocationUseAsEnum::Address
            ]);

            if ($request->filled('logo')) { // Save the charity's logo
                $this->attachSingleUploadToModel($charity, $request->logo, UploadUseAsEnum::Logo);
            }

            if (AccountType::isAccountManager()) { // Assign the account manager to the charity
                $charity->assignToAccountManager(Auth::user());
            } else if ($request->filled('account_manager')) { // Assign the account manager to the charity
                $user = User::where('ref', $request->account_manager)->firstOrFail();
                $charity->assignToAccountManager($user);
            }

            // Save charity profile
            $this->saveProfile($request, $charity);

            if (AccountType::isAdmin()) { // Save charity meta data
                $this->saveMetaData($request, $charity); // Save meta data
            }

            // Generate the user password password
            $password = Str::random(8);

            // Create the charity owner
            $user = $this->saveOwner($request, $charity, $password);

            // Notify the charity owner
            Mail::site()->to($user->email)->send(new CharityAccountCreatedMail($user, $password));

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to create the charity! Please try again', 406, $e->getMessage());
        } catch (FileException $e) {
            DB::rollback();

            return $this->error('Unable to create the charity! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully created the charity!', 200, new CharityResource($charity->load(['charityProfiles', 'meta', 'uploads'])));
    }

    /**
     * Get a charity's details.
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $charity = Charity::where('id', $id)
            ->filterByAccess();

        try {
            $charity = $charity->firstOrFail();

        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        return $this->success('The charity details', 200, new CharityResource($charity));
    }

    /**
     * Update charity profile.
     *
     * @param  CharityUpdateProfileRequest $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function updateProfile(CharityUpdateProfileRequest $request, int $id): JsonResponse
    {
        $charity = Charity::with(['charityOwner.user'])
            ->filterByAccess()
            ->where('id', $id);

        try {
            $charity = $charity->firstOrFail();

            $charityOwner['email'] = $request->email;
            if (isset($request->password)) {
                $charityOwner['password'] = \Hash::make($request->password);
            } // else {
            //     unset($request['password']);
            // }
            // Update the charity owner
            $charity->charityOwner?->user->update($charityOwner);

            // Update charity information
            $charity->email = $request->support_email;
            $charity->name = $request->name;
            $charity->city = $request->city;
            $charity->country = $request->country;
            $charity->postcode = $request->postcode;
            $charity->phone = $request->phone;
            $charity->charity_category_id = CharityCategory::where('slug', $request->category)->first()?->id;
            $charity->finance_contact_name = $request->finance_contact_name ?? null;
            $charity->finance_contact_email = $request->finance_contact_email ?? null;
            $charity->finance_contact_phone = $request->finance_contact_phone ?? null;

            if (AccountType::isAdminOrAccountManagerOrDeveloper()) {
                $charity->show_in_external_feeds = $request->show_in_external_feeds ?? 0;
                $charity->show_in_vmm_external_feeds = $request->show_in_vmm_external_feeds ?? 0;
                $charity->external_strapline = $request->external_strapline ?? null;
            }

            try {
                DB::beginTransaction();

                $charity->save();

                $charity->location()->createOrUpdate([], [ // Create the charity address
                    'address' => $request->address,
                    'use_as' => LocationUseAsEnum::Address
                ]);

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to update the charity! Please try again', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The charity was not found!', 404);
        }

        return $this->success('Successfully updated the charity!', 200, new CharityResource($charity));
    }

    /**
     * Delete a charity (Soft deletes)
     *
     * @urlParam id int required The id of the charity. Example: 336
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

            try {
                DB::beginTransaction();

                $charity->delete();

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to delete the charity! Please try again', 406);
            }

        } catch (ModelNotFoundException $e) {
            return $this->error('The charity was not found!', 404);
        }

        return $this->success('Successfully deleted the charity!', 200, new CharityResource($charity));
    }

    /**
     * Delete a charity (Permanently)
     *
     * @urlParam id int required The id of the charity. Example: 336
     * @return JsonResponse
     */
    public function destroyPermanently(int $id): JsonResponse
    {
        if (! AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->withTrashed()->firstOrFail();

            try {
                DB::beginTransaction();

                $charity->forceDelete();

                // if ($charity->image && Storage::disk(config('filesystems.default'))->exists($charity->image->url)) { // Delete the existing image if it exists
                //     Storage::disk(config('filesystems.default'))->delete($charity->image->url);
                // }

                // if ($charity->images) { // Delete the existing charity images if they exists
                //     foreach ($charity->images as $image) {
                //         if ($image->url && Storage::disk(config('filesystems.default'))->exists($image->url)) Storage::disk(config('filesystems.default'))->delete($image->url);
                //     }
                // }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to delete the charity! Please try again', 406);
            }

        } catch (ModelNotFoundException $e) {
            return $this->error('The charity was not found!', 404);
        }

        return $this->success('Successfully deleted the charity!', 200, new CharityResource($charity));
    }

    /**
     * Get branding
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function branding(int $id): JsonResponse
    {
        $charity = Charity::with(['logo'])
            ->filterByAccess()
            ->where('id', $id);

        try {
            $charity = $charity->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return $this->error('The charity was not found!', 404);
        }

        $charity->percentage = Charity::percentComplete($charity);

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'primary_color', 'secondary_color', 'percentage', 'logo']);

        return $this->success('The charity branding', 200, new CharityResource($charity));
    }

    /**
     * Update branding
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function updateBranding(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Primary header colour. Example: #3DFF1F
            'primary_color' => 'sometimes|required|string',
            // Secondary header colour. Example: #FF4A1C
            'secondary_color' => 'sometimes|required|string',
            'logo' => ['sometimes', 'required', 'base64image', 'base64mimes:jpeg,png,jpg,gif,svg,webp,avif', 'base64max:10240']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charity = Charity::with(['logo'])
            ->filterByAccess()
            ->where('id', $id);

        try {
            $charity = $charity->firstOrFail();

            try {
                if (isset($request->primary_color)) {
                    $request['primary_color'] = Colour::pantone2hex($request->primary_color);
                }

                if (isset($request->secondary_color)) {
                    $request['secondary_color'] = Colour::pantone2hex($request->secondary_color);
                }

                if ($request->filled('logo')) { // Save the charity's logo
                    $this->attachSingleUploadToModel($charity, $request->logo, UploadUseAsEnum::Logo);
                }

                $charity->update($request->only(['primary_color', 'secondary_color']));

            } catch (QueryException $e) {

                return $this->error('Unable to update the branding! Please try again', 406);
            } catch (FileException $e) {

                return $this->error('Unable to create the charity! Please try again', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity->load('logo');

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'primary_color', 'secondary_color', 'percentage', 'logo']);

        return $this->success('Successfully updated the branding!', 200, new CharityResource($charity));
    }

    /**
     * Get content
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function content(Request $request, int $id): JsonResponse
    {
        try {
            $charity = Charity::with(['charityProfile', 'images' => function ($query) {
                    $query->where('site_id', static::getSite()?->id);
                }, 'meta', 'socials'])
                ->filterByAccess()
                ->where('id', $id);

            $charity = $charity->firstOrFail();

        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'donation_link', 'website', 'socials', 'charityProfile', 'images', 'meta']);

        return $this->success('The charity content', 200, new CharityResource($charity));
    }

    /**
     * Update content
     *
     * @param  CharityUpdateContentRequest  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function updateContent(CharityUpdateContentRequest $request, int $id): JsonResponse
    {
        $charity = Charity::with(['charityProfile', 'images' => function ($query) {
                $query->where('site_id', static::getSite()?->id);
            }, 'meta'])
            ->filterByAccess()
            ->where('id', $id);

        try {
            $charity = $charity->firstOrFail();

            try {
                if ($request->filled('images')) { // Save the charity's images | TODO: Look deeper into these images upload and improve on it by adding a possibility to add an image(s) without deleting the existing ones (you may use a different route for this). Also use a route to delete an image.
                    $this->attachMultipleUploadsToModel($charity, $request->images);
                }

                if (AccountType::isAdmin()) {
                    $this->saveMetaData($request, $charity); // Save meta data
                }

                // Create/update charity profile
                $charityProfile = $request->only(['description', 'mission_values', 'video']);
                $charityProfile['site_id'] = static::getSite()?->id;
                $charity->charityProfile()->updateOrCreate([], $charityProfile);

                if ($request->socials && $request->socials[0]['platform']) { // Update the charity's socials
                    foreach ($request->socials as $social) {
                        $charity->socials()->updateOrCreate([
                            'platform' => $social['platform'],
                        ], [
                            'url' => $social['url']
                        ]);
                    }
                }

                // Update the charity
                $charity->update($request->only(['donation_link', 'website']));

            } catch (QueryException $e) {

                return $this->error('Unable to update the content! Please try again', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity->load(['images', 'charityProfile', 'meta', 'socials']);

        $charity = $charity->only([ 'id', 'ref', 'name', 'slug', 'donation_link', 'website', 'socials', 'charityProfile', 'images', 'meta']);

        return $this->success('Successfully updated the content!', 200, new CharityResource($charity));
    }

    /**
     * Remove the logo or an image from the charity's images.
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @urlParam upload_id int required The id of the image. Example: 8
     * @return JsonResponse
     */
    public function removeImage(int $id, int $upload_id): JsonResponse
    {
        $charity = Charity::with('uploads');

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->findOrFail($id);

            try {
                $upload = $charity->uploads()->where('id', $upload_id)->firstOrFail();

                try {
                    $this->detachUpload($charity, $upload->ref);

                } catch (QueryException $e) {

                    return $this->error('Unable to delete the image! Please try again', 406, $e->getMessage());
                }

            } catch (ModelNotFoundException $e) {

                return $this->error('The image was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity->load('images');

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'images']);

        return $this->success('Successfully deleted the image!', 200, new CharityResource($charity));
    }

    /**
     * Get memberships
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function memberships(int $id): JsonResponse
    {
        try {
            $charity = Charity::with(['charityMemberships', 'charityManager.user.contracts' => function ($query) {
                    $query->where('state', ContractStateEnum::Current);
                }, 'donations', 'latestCharityMembership', ])
                ->filterByAccess()
                ->where('id', $id);

            $charity = $charity->firstOrFail();
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'charityMemberships', 'charityManager', 'donations', 'latestCharityMembership', 'previousCharityMembership']);

        return $this->success('The charity memberships', 200, new CharityResource($charity));
    }

    /**
     * Update memberships
     *
     * @param  CharityMembershipRequest  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function updateMemberships(CharityMembershipRequest $request, int $id): JsonResponse
    {
        $charity = Charity::with(['charityMemberships', 'charityManager.user.contracts' => function ($query) {
                $query->where('state', ContractStateEnum::Current);
            }, 'donations', 'latestCharityMembership'])
            ->where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

            try {
                DB::beginTransaction();

                if ($charity->latestCharityMembership) { // Update the status of the latest membership if it exists
                    $charity->latestCharityMembership()->update(['status' => CharityMembership::INACTIVE]);
                }

                $request['expiry_date'] = Carbon::parse($request->expiry_date);

                if ($request->extend_membership) { // extend membership
                    if ($request->type === CharityMembershipTypeEnum::TwoYear) {
                        $request['expiry_date'] = Carbon::parse($charity->expiry_date)->addYears(2);
                    } else {
                        $request['expiry_date'] = Carbon::parse($charity->expiry_date)->addYear();
                    }
                }

                $request['renewed_on'] = Carbon::now();
                $request['status'] = CharityMembership::ACTIVE;

                if (AccountType::isAccountManager()) { // Assign the account manager to the charity
                    $charity->assignToAccountManager(Auth::user());
                } else if ($request->filled('account_manager')) { // Assign the account manager to the charity
                    $user = User::where('ref', $request->account_manager)->firstOrFail();
                    $charity->assignToAccountManager($user);
                }

                $charity->charityMemberships()->create($request->only(['status', 'expiry_date', 'type', 'use_new_membership_fee', 'renewed_on']));

                $charity->load('latestCharityMembership');

                if ($request->extend_membership) {
                    if ($charity->latestCharityMembership->use_new_membership_fee) {
                        // Add credits to charity account
                        $charity->addCredits();

                        // Create LDT campaigns
                        $charity->createCampaign();
                    }

                    $invoiceDate = isset($request->invoice['send_on']) ? $request->invoice['send_on'] : Carbon::now();

                    $invoice = $charity->invoices()->create([ // Create the invoice
                        'date' => $invoiceDate,
                        'send_on' => $invoiceDate,
                        'price' => $charity->latestCharityMembership->membership_fee,
                        'status' => InvoiceStatusEnum::Unpaid,
                        'held' => (int) $request->invoice['held'],
                        'line' => 'Invoice for Charity Membership',
                        'type' => InvoiceItemTypeEnum::CharityMembership,
                        'user_id' => $charity->charityOwner?->user?->id
                    ]);

                    if ($invoice && !$invoice->held) {
                        $this->dispatch(new SendMembershipRenewalInvoiceJob($charity, $invoice));
                    }
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to update the charity membership! Please try again', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The charity was not found!', 404);

        }

        $charity->load(['charityMemberships', 'previousCharityMembership']);

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'charityManager', 'charityMemberships', 'previousCharityMembership']);

        return $this->success('Successfully updated the charity membership!', 200, new CharityResource($charity));
    }

    /**
     * Get fundraising platform
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function fundraisingPlatform(int $id): JsonResponse
    {
        try {
            $charity = Charity::where('id', $id)
                ->filterByAccess()
                ->firstOrFail();

        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'fundraising_platform', 'fundraising_platform_url', 'fundraising_ideas_url']);

        return $this->success('The charity fundraising platform', 200, new CharityResource($charity));
    }

    /**
     * Update fundraising platform
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function updateFundraisingPlatform(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Must be one of just_giving, virgin_money_giving, custom.
            // TODO: Update this after discussing with lead. It has been noticed that only the custom option is used (from the portal) and database records only have the custom or null options as values.
            'fundraising_platform' => ['required', 'in:just_giving,virgin_money_giving,custom'],
            // Must be a valid URL. Example: https://www.justgiving.com/wwf
            'fundraising_platform_url' => ['required', 'active_url'],
            // Must be a valid URL. Example: https://www.justgiving.com/wwf
            'fundraising_ideas_url' => ['nullable', 'active_url']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charity = Charity::where('id', $id)
            ->filterByAccess();

        try {
            $charity = $charity->firstOrFail();

            try {
                $charity->update([
                    'fundraising_platform' => $request->fundraising_platform,
                    'fundraising_platform_url' => $request->fundraising_platform_url,
                    'fundraising_ideas_url' => $request->fundraising_ideas_url ?? null,
                ]);

            } catch (QueryException $e) {

                return $this->error('Unable to update fundraising platform! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'fundraising_platform', 'fundraising_platform_url', 'fundraising_ideas_url']);

        return $this->success('Successfully updated the fundraising platform!', 200, new CharityResource($charity));
    }

    /**
     * Get eventsIncluded (the only_included_charities events the charity is allowed to run).
     * That is, the events that can only be run by some charities (only_included_charities) for which the charity is among).
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function eventsIncluded(Request $request, int $id): JsonResponse
    {
        try {
            $charity = Charity::with(['eventsIncluded'])
                ->filterByAccess()
                ->where('id', $id)
                ->firstOrFail();

            $events = [];

            if (AccountType::isAdminOrAccountManagerOrDeveloper()) {
                $events = Event::onlyIncludedCharities();
            }

        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'eventsIncluded']);
        // $events = $events->only(['id', 'ref', 'name', 'slug']);

        return $this->success('The charity included events', 200, [
            'charity' => new CharityResource($charity),
            'events' => new EventResource($events)
        ]);
    }

    /**
     * Update eventsIncluded (the only_included_charities events the charity is allowed to run).
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function updateEventsIncluded(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The event ids. Example: [153, 155, 234, 325, 32424]
            'event_ids' => ['required', 'array', 'exists:events,id'],
            'event_ids.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charity = Charity::with(['eventsIncluded'])
            ->filterByAccess()
            ->where('id', $id);

        try {
            $charity = $charity->firstOrFail();

            try {
                $charity->eventsIncluded()->syncWithPivotValues($request->event_ids, ['type' => CharityEventTypeEnum::Included]);

                $events = Event::onlyIncludedCharities();

            } catch (QueryException $e) {

                return $this->error('Unable to update events included! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity->load('eventsIncluded');

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'eventsIncluded']);
        // $events = $events->only(['id', 'ref', 'name', 'slug']);

        return $this->success('Successfully updated the events included!', 200, [
            'events' => new EventResource($events),
            'charity' => new CharityResource($charity),
        ]);
    }

    /**
     * Toggle external enquiries notification frequency (settings)
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */

    public function toggleExternalEnquiryNotifications(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'external_enquiry_notification_settings' => 'required|in:each,daily,weekly,monthly'
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charity = Charity::where('id', $id)
            ->filterByAccess();

        try {
            $charity = $charity->firstOrFail();

            try {
                $charity->update([
                    'external_enquiry_notification_settings' => $request->external_enquiry_notification_settings
                ]);
            } catch (QueryException $e) {

                return $this->error('Unable to update external enquiry notification settings! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        return $this->success('Successfully updated notification settings!', 200, new CharityResource($charity));
    }

    /**
     * Toggle complete registration notification frequency (settings)
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function toggleCompleteRegistrationNotifications(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'complete_notifications' => 'required|in:always,weekly,monthly' // change always to each
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charity = Charity::where('id', $id)
            ->filterByAccess();

        try {
            $charity = $charity->firstOrFail();

            try {
                $charity->update([
                    'complete_notifications' => $request->complete_notifications
                ]);
            } catch (QueryException $e) {

                return $this->error('Unable to update complete registrations notification settings! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        return $this->success('Successfully updated notification settings!', 200, new CharityResource($charity));
    }

    /**
     * Toggle fundraising emails integration settings
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function toggleFundraisingEmailIntegration(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fundraising_emails_active' => ['required', 'boolean']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

            try {
                $charity->update([
                    'fundraising_emails_active' => $request->fundraising_emails_active
                ]);

                $charity->refresh();

                $message = $charity->fundraising_emails_active ? 'activated' : 'deactivated';

            } catch (QueryException $e) {

                return $this->error('Unable to update fundraising email integration! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        return $this->success("Successfully {$message} fundraising email integration", 200, new CharityResource($charity));
    }

    /**
     * toggle charity checkout integration settings
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function toggleCharityCheckoutIntegration(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charity_checkout_integration' => ['required', 'boolean']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = Charity::firstOrFail();

            try {
                $charity->update([
                    'charity_checkout_integration' => $request->charity_checkout_integration
                ]);

                $message = $charity->charity_checkout_integration ? 'enabled' : 'disabled';

            } catch (QueryException $e) {

                return $this->error('Unable to update notification settings! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        return $this->success("Successfully {$message} the charity checkout integration", 200, new CharityResource($charity));
    }

    /**
     * Get call notes
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function callNotes(Request $request, int $id): JsonResponse
    {
        $charity = Charity::with('callNotes')
            ->where('id', $id);

        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();


        } catch (ModelNotFoundException $e) {
            return $this->error('The charity was not found!', 404);
        }

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'manager_call_notes', 'manager_call_status', 'callNotes']);

        return $this->success('The charity call notes', 200, new CharityResource($charity));
    }

    /**
     * Update manager call note.
     * These are a charity call notes highlights often set by account managers.
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function updateManagerCallNote(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Example: 26/07 - Spoke to Fru and he is chasing the invoice payment. 08/09 - Good Meeting with Jodie. Went through what to promote and also booked in meeting for bespoke virtual Santa Dash
            'manager_call_notes' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

            try {
                $charity->update(['manager_call_notes' => $request->manager_call_notes]);

            } catch (QueryException $e) {

                return $this->error('Unable to update manager call note! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        return $this->success('Successfully updated the manager call note', 200, new CharityResource($charity));
    }

    /**
     * Create/update a call note.
     *
     * @param CharityCallNoteRequest $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function createCallNote(CharityCallNoteRequest $request, int $id): JsonResponse
    {
        $charity = Charity::with(['callNotes'])
            ->where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

            try {
                $callNote = $charity->callNotes()->updateOrCreate([
                    'charity_id' => $id,
                    'year' => $request->year,
                    'call' => $request->call
                ], [
                    'note' => $request->note,
                    'status' => $request->status,
                ]);

                $callNote->save();

            } catch (QueryException $e) {

                return $this->error('Unable to save the call note! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity->load('callNotes');
        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'callNotes']);

        return $this->success("Successfully saved the call note!", 200, new CharityResource($charity));
    }

    /**
     * Update a call note
     *
     * @param CharityCallNoteRequest $request
     * @urlParam id int required The id of the charity. Example: 335
     * @urlParam call_note_id int required The id of the call note. Example: 8
     * @return JsonResponse
     */
    public function updateCallNote(CharityCallNoteRequest $request, int $id, int $call_note_id): JsonResponse
    {
        $charity = Charity::with(['callNotes'])
            ->where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

            try {
                $callNote = CallNote::where('id', $call_note_id)
                    ->where('charity_id', $id)
                    ->firstOrFail();

                try {
                    $callNote->update($request->only(['year', 'status', 'note', 'call']));

                } catch (QueryException $e) {

                    return $this->error('Unable to update the call note! Please try again.', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {

                return $this->error('The call note was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity->load('callNotes');

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'callNotes']);

        return $this->success('Successfully updated the call note!', 200, new CharityResource($charity));
    }

    /**
     * Delete a call note
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @urlParam call_note_id int required The id of the call note. Example: 8
     * @return JsonResponse
     */
    public function deleteCallNote(int $id, int $call_note_id): JsonResponse
    {
        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

            try {
                $callNote = CallNote::where('id', $call_note_id)
                    ->where('charity_id', $id)
                    ->firstOrFail();

                try {
                    $callNote->delete();

                } catch (QueryException $e) {

                    return $this->error('Unable to delete the call note! Please try again', 406);
                }
            } catch (ModelNotFoundException $e) {

                return $this->error('The call note was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity->load('callNotes');

        $charity = $charity->only(['id', 'ref', 'name', 'slug', 'callNotes']);

        return $this->success('Successfully deleted the call note!', 200, new CharityResource($charity));
    }

    /**
     * Export Charities.
     * @queryParam membership_type string Filter by membership type. Example: premium
     * @queryParam status bool Filter by status. Example 1
     * @queryParam category string Filter by charity category slug. Example: cancer-children-youth
     * @queryParam term string Filter by string. No-example
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(Request $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        $charities = Charity::with('charityManager', 'charityCategory');

        if (AccountType::isAccountManager()) {
            $charities = $charities->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        if ($request->filled('category')) {
            $charities = $charities->whereHas('charityCategory', function ($query) use ($request) {
                $query->where('slug', $request->category);
            });
        }

        if ($request->filled('status')) {
            $charities = $charities->where('status', $request->status);
        }

        if ($request->filled('term')) {
            $charities = $charities->where('name', 'LIKE', '%'.$request->term.'%');
        }

        if ($request->filled('membership_type')) {
            $charities = $charities->whereHas('latestCharityMembership', function ($query) use ($request) {
                $query->where('type', $request->membership_type);
            });
        }

        $charities = $charities->orderBy('name', 'asc')->get();

        $finalCharities = [];

        foreach ($charities as $charity) {
            $temp['Status'] = $charity->status ? 'Active' : 'Inactive';
            $temp['Owner'] = $charity->charityOwner?->user?->full_name;
            $temp['Manager'] = $charity->charityManager?->user?->full_name;
            $temp['Category'] = $charity->charityCategory?->name;
            $temp['Primary_color'] = $charity->primary_color;
            $temp['Secondary_color'] = $charity->secondary_color;
            $temp['Slug'] = $charity->slug;
            $temp['Email'] = $charity->charityOwner?->user?->email;
            $temp['Support_email'] = $charity->email;
            $temp['Name'] = $charity->name;
            $temp['Description'] = $charity->charityProfile?->description;
            $temp['Logo'] = $charity->logo?->url;
            $temp['Video'] = $charity->charityProfile?->video;
            $temp['Video_id'] = $charity->charityProfile?->video_id;
            $temp['Website'] = $charity->website;
            $temp['Phone'] = $charity->phone;
            $temp['Address'] = $charity->location?->address;
            $temp['Postcode'] = $charity->postcode;
            $temp['City'] = $charity->city;
            $temp['Country'] = $charity->country;
            $temp['Social_facebook'] = $charity->social_facebook;
            $temp['Social_twitter'] = $charity->social_twitter;
            $temp['Social_instagram'] = $charity->social_instagram;
            $temp['Donation_link'] = $charity->donation_link;
            $temp['Membership_type'] = trim(preg_replace('/([A-Z])/', ' $1', $charity->latestCharityMembership?->type->name));
            $temp['Expiry_date'] = Carbon::parse($charity->latestCharityMembership?->expiry_date)->toFormattedDateString();
            $temp['Show_in_external_feeds'] = $charity->show_in_external_feeds;
            $temp['External_strapline'] = $charity->external_strapline;
            $temp['Charity_checkout_id'] = $charity->charity_checkout_id;
            $temp['Fundraising_platform_url'] = $charity->fundraising_platform_url;

            // Purify the HTML text
            $temp['Mission_values'] = TextHelper::purify($charity->charitProfile?->mission_values);

            $finalCharities[] = $temp;
        }

        if (!$finalCharities) {
            return $this->error('The charities were not found', 406);
        }

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        $fileName = 'Charities - ' . date('Y-m-d H:i:s') . '.csv';
        Excel::store(new CharityCsvExport($finalCharities), $fileName, 'csvs', \Maatwebsite\Excel\Excel::CSV, $headers);
        $path = config('app.csvs_path'). '/'. $fileName;

        return static::_download($path, true);
    }

    /**
     * Get the invoices of the charity.
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function invoices(int $id): JsonResponse
    {
        $charity = Charity::with(['invoices.user'])
            ->filterByAccess()
            ->where('id', $id);

        try {
            $charity = $charity->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return $this->error('The charity was not found!', 404);
        }

        return $this->success('The charity\'s invoices', 200, new CharityResource($charity));
    }

    /**
     * Create an invoice.
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function createInvoice(int $id): JsonResponse
    {
        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return $this->error('The charity was not found!', 404);
        }

        return $this->success('Create an invoice', 200, [
            'charity' => new CharityResource($charity),
            'types' => InvoiceItemTypeEnum::cases()
        ]);
    }

    /**
     * Store the invoice.
     * Replaces postCreateInvoice method
     *
     * @param InvoiceCreateRequest $request
     * @urlParam id int required The id of the charity. Example: 335
     * @return JsonResponse
     */
    public function storeInvoice(InvoiceCreateRequest $request, int $id): JsonResponse
    {
        $charity = Charity::where('id', $id);

        if (AccountType::isAccountManager()) {
            $charity = $charity->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        try {
            $charity = $charity->firstOrFail();

            try {
                DB::beginTransaction();

                $request['date'] = $request->send_on ?? Carbon::now();
                $request['status'] = InvoiceStatusEnum::Unpaid->value;
                $request['user_id'] = $charity->charityOwner?->user?->id;

                $invoice = $charity->invoices()->create($request->all());

                $path = config('app.pdfs_path');

                if ($request->file('pdf') && $request->file('pdf')->isValid()) { // Upload the pdf file
                    if ($fileName = $this->moveUploadedFile($request->file('pdf'), $path, UploadUseAsEnum::Image)) {
                        $invoice->upload()->create([], [
                            'url' => $path.$fileName,
                            'title' => $invoice->name,
                            'type' => UploadTypeEnum::PDF,
                            'use_as' => UploadUseAsEnum::PDF
                        ]);
                    }
                }

                if ($request && !$request->held) {
                    // Dispatch a job to notify the charity via email.
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to save the invoice! Please try again.', 406, $e->getMessage());
            }

        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        $charity->load(['invoices.user', 'invoices.upload']);

        return $this->success('Successfully created the charity\'s invoice!', 200, new CharityResource($charity));
    }

    /**
     * Delete an invoice and refund the charity.
     * TODO: Review this implementation after the Participant Model would have been created and while working on the invoice controller.
     *
     * @urlParam id int required The id of the charity. Example: 335
     * @urlParam invoice_id int required The id of the charity. Example: 1
     * @return JsonResponse
     */
    public function deleteInvoice(int $id, int $invoice_id): JsonResponse
    {
        if (! AccountType::isAdmin()) {
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            DB::beginTransaction();

            $charity = Charity::with(['invoices'])->findOrFail($id);

            try {
                $invoice = $charity->invoices()->where('id', $invoice_id)->firstOrFail();

                try {
                    if ($invoice->status == InvoiceStatusEnum::Paid) {
                        if (! $invoice->charge_id) {
                            throw new ModelNotFoundException('There is no charge stored against this invoice.');
                        }

                        // $refund = Stripe::refund($invoice->charge_id);

                        // $invoice->update(['refund_id' => $refund->id]);
                    }

                    $invoice->participants()?->update(['invoice_id' => null]);

                    $invoice->delete();

                    DB::commit();
                } catch (QueryException $e) {
                    DB::rollback();

                    return $this->error('Unable to delete the charity\'s invoice! Please try again', 406);
                }  catch (ModelNotFoundException $e) {

                    return $this->error($e->getMessage(), 404);
                }
            } catch (ModelNotFoundException) {

                return $this->error('The invoice was not found! | An unknown error occurred', 404);
            }

        } catch (ModelNotFoundException $e) {

            return $this->error('The charity was not found!', 404);
        }

        return $this->success('Successfully deleted the charity\'s invoice!', 200, new CharityResource($charity->load('invoices')));
    }

    /**
     * Save the charity profile.
     *
     * @param  Request  $request
     * @param  Charity  $charity
     * @return CharityProfile
     */
    private function saveProfile(Request $request, Charity $charity): CharityProfile
    {
        return $charity->charityProfiles()->create([
            'site_id' => static::getSite()?->id,
            'description' => $request->description,
            'mission_values' => $request->mission_values,
            'video' => $request->video
        ]);
    }

    /**
     * Save the charity owner.
     *
     * @param  Request  $request
     * @param  Charity  $charity
     * @param  string  $password
     * @return User
     */
    private function saveOwner(Request $request, Charity $charity,  string $password): User
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => trim($request->email),
            'temp_pass' => 1, // Prompts the user to update it's password upon authentication.
            'password' => \Hash::make($password),
        ]);

        $charity->assignToUser($user, CharityUserTypeEnum::Owner);

        return $user;
    }
}
