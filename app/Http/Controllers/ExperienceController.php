<?php

namespace App\Http\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\ExperienceDataService;
use App\Traits\Response;
use App\Models\Experience;
use App\Enums\ListTypeEnum;
use App\Filters\YearFilter;
use App\Facades\ClientOptions;
use App\Filters\DeletedFilter;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Enums\OrderByDirectionEnum;
use App\Filters\ExperienceOrderByFilter;
use App\Services\DefaultQueryParamService;
use App\Http\Requests\StoreExperienceRequest;
use App\Http\Requests\UpdateExperienceRequest;
use App\Http\Requests\DeleteExperienceRequest;
use App\Enums\ExperiencesListOrderByFieldsEnum;
use App\Filters\DraftedFilter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\ExperienceListingQueryParamsRequest;
use App\Http\Requests\RestoreExperiencesRequest;
use App\Traits\DraftCustomValidator;
use Google\Service\BackupforGKE\Restore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExperienceController extends Controller
{
    use Response, DraftCustomValidator;

    /**
     * Get Experiences
     *
     * Endpoint lists available experiences.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam term string Filter by term. No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:asc,created_at:asc
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam page string Pagination page to be fetched. Example: 1
     *
     * @param  ExperienceListingQueryParamsRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ExperienceListingQueryParamsRequest $request): \Illuminate\Http\JsonResponse
    {
        $query = Experience::whereHas('site', function ($query) {
            $query->makingRequest();
        })->filterListBy(new ExperienceOrderByFilter)
        ->filterListBy(new YearFilter)
        ->filterListBy(new DraftedFilter)
        ->filterListBy(new DeletedFilter);

        $perPage = request('per_page');
        $term = request('term');
        $orderBy = request('order_by');

        $experiences = $query->when(
            $term,
            fn($query) => $query->where('name', 'like', "%{$term}%")
        )->when(! $orderBy, // Default Ordering
            fn($query) => $query->orderBy('name')
        )->when(
            $perPage,
            fn($query) => $query->paginate((int) $perPage),
            fn ($query) => $query->paginate(10)
        )->withQueryString();

        return $this->success('The list of experiences', 200, [
            'experiences' => $experiences,
            'options' => ClientOptions::only('experiences', [
                'drafted',
                'deleted',
                'order_by',
                'order_direction',
                'years'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Experiences))
                ->setParams(['order_by' => ExperiencesListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                ->getDefaultQueryParams(),
            'action_messages' => Experience::$actionMessages
        ]);
    }

    /**
     * Show Experience
     *
     * Retrieve info about specified experience.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam experience_ref string required The ref attribute of the experience. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param Experience $experience
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Experience $experience): \Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('The experience.', 200, [
                'experience' => Experience::where('ref', $experience->ref)
                    ->whereHas('site', function ($query) {
                        $query->makingRequest();
                    })->withDrafted()->firstOrFail(),
                'action_messages' => Experience::$actionMessages
            ]);
        } catch (ModelNotFoundException|NotFoundHttpException $exception) {
            return $this->error('We could not find the experience you were looking for.', 400);
        }
    }

    /**
     * Add Experience
     *
     * Add a new experience to the system.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam name string required The name of the new experience. Example: Atmosphere
     * @bodyParam icon string required The icon associated to experience
     * @bodyParam values string[] required Specify values for experiences. Example: ["Amazing", "Unbelievable", "Exciting"]
     *
     * @param \App\Http\Requests\StoreExperienceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreExperienceRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $experience = Experience::create($request->validated());

            return $this->success('Successfully added an experience!', 201, [
                'experience' => $experience
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to add experience.', 400);
        }
    }

    /**
     * Update Experience
     *
     * Update existing experience.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam name string required The name of the new experience. Example: Atmosphere
     * @bodyParam icon string required The icon associated to experience
     * @bodyParam values string[] required Specify values for experiences. Example: ["Amazing", "Unbelievable", "Exciting"]
     * @urlParam experience_ref string required The ref attribute of the experience. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param UpdateExperienceRequest $request
     * @param Experience $experience
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateExperienceRequest $request, Experience $experience): \Illuminate\Http\JsonResponse
    {
        try {
            $experience->update($request->validated());

            return $this->success('Successfully updated the experience!', 200, [
                'experience' => $experience,
            ]);
        } catch (ModelNotFoundException|NotFoundHttpException $exception) {
            return $this->error('We could not find the experience you were trying to update.', 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update experience', 400);
        }
    }

    /**
     * Mark one or more experiences as published
     *
     * Publishing experience.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required The array of valid ids belonging to experiences to be published. Example: [1, 2, 3]
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsPublished(Request $request)
    {
        $validator =  Validator::make($request->all(), $this->markAsPublishedValidationRules('experiences'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            Experience::whereIntegerInRaw('id', $request->ids)->onlyDrafted()->markAsPublished();

            CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);

            return $this->success('Successfully published the specified experience(s).', 200);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to publish experience(s).', 400);
        }
    }


    /**
     * Mark one or more experiences as draft
     *
     * Drafting experience.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required The array of valid ids belonging to experiences to be drafted. Example: [1, 2, 3]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request): JsonResponse
    {
        $validator =  Validator::make($request->all(), $this->markAsDraftValidationRules('experiences'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            Experience::whereIntegerInRaw('id', $request->ids)->markAsDraft();

            CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);

            return $this ->success('Successfully drafted the specified experience(s).', 200);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to draft experience(s).', 400);
        }
    }

    /**
     * Delete Experience
     *
     * Deleting experience.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam experience_ref string required The ref attribute of the experience. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param  \App\Models\Experience  $experience
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Experience $experience): \Illuminate\Http\JsonResponse
    {
        try {
            Experience::where('id', $experience->id)
                ->whereHas('site', fn ($query) => $query->makingRequest())
                ->withDrafted()
                ->delete();

            return $this->success('Successfully deleted the experience!', 200, [
                'experiences' => Experience::whereHas('site', function ($query) {
                    $query->makingRequest();
                })->latest()->paginate(10)
            ]);
        } catch (ModelNotFoundException|NotFoundHttpException $exception) {
            return $this->error('We could not find the experience you were trying to delete.', 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete experience.', 400);
        }
    }

    /**
     * Restore Experience
     *
     * restoring experience.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam experiences string[] required The array of valid ids belonging to experiences to be deleted. Example: [1, 2, 3]
 * @param  mixed $request
     * @return JsonResponse
     */
    public function restoreMany(RestoreExperiencesRequest $request): JsonResponse
    {
        try {
            Experience::whereHas('site', fn ($query) => $query->makingRequest())
                ->whereIn('id', $request->validated('experiences'))
                ->withDrafted()
                ->restore();

            CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);

            return $this->success('Successfully restored the specified experience(s).', 200, [
                'experiences' => Experience::whereHas('site', function ($query) {
                    $query->makingRequest();
                })->latest()->paginate(10)
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to restore experience(s).', 400);
        }
    }

    /**
     * Experiences Multi-deletion
     *
     * Deleting Multiple Experiences
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam experiences string[] required The array of valid ids belonging to experiences to be deleted. Example: [1, 2, 3]
     *
     * @param DeleteExperienceRequest $request
     * @return JsonResponse
     */
    public function destroyMany(DeleteExperienceRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            Experience::whereHas('site', fn ($query) => $query->makingRequest())
                ->whereIn('id', $request->validated('experiences'))
                ->withDrafted()
                ->delete();

            CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);

            return $this->success('Successfully deleted the specified experience(s).', 200, [
                'experiences' => Experience::whereHas('site', function ($query) {
                    $query->makingRequest();
                })->latest()->paginate(10)
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete specified experience(s).', 400, $exception->getMessage());
        }
    }

    /**
     * Experiences Permanent Multi-deletion
     *
     * Deleting Multiple Experiences Permanently
     *
     * @group Experience
     * @authenticateds
     * @header Content-Type application/json
     *
     * @bodyParam experiences string[] required The array of valid ids belonging to experiences to be deleted permanently. Example: [1, 2, 3]
     *
     * @param DeleteExperienceRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(DeleteExperienceRequest $request): \Illuminate\Http\JsonResponse
    {
        if (! AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            Experience::withTrashed()
                ->whereHas('site', fn ($query) => $query->makingRequest())
                ->whereIn('id', $request->validated('experiences'))
                ->withDrafted()
                ->forceDelete();

            CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);

            return $this->success('Successfully deleted the specified experience(s) permanently.', 200, [
                'experiences' => Experience::whereHas('site', function ($query) {
                    $query->makingRequest();
                })->latest()->paginate(10)
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to permanently delete specified experience(s).', 400);
        }
    }
}
