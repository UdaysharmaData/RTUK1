<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Models\City;
use App\Models\Page;
use App\Models\Region;
use App\Models\Venue;
use App\Traits\Response;
use App\Models\Combination;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventCategory;

use App\Enums\SearchableOptionsEnum;
use App\Models\SearchHistory;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\GlobalSearchService\GlobalSearch;
use App\Services\GlobalSearchService\Exceptions\InvalidSearchableModel;
use App\Services\GlobalSearchService\Exceptions\InvalidSearchableOptionException;
use Aws\History;
use Google\Service\CustomSearchAPI\Search;

/**
 * @group Search Management
 *
 * APIs for managing user search
 */

class SearchController extends Controller
{
    use Response;

    public function __construct(protected GlobalSearchDataService $globalSearchService)
    {
        parent::__construct();
    }

    /**
     * Search for a term
     *
     * @urlParam option string required The option to apply to the search results. Possible values are: all, events, categories, regions, cities, venues, combinations, charities, pages, recent
     * @queryParam term string required The search term
     * @queryParam per_page int The number of results to return per page
     * @queryParam page int The page number
     * @queryParam recent_search_terms_limit int The number of recent search terms to return
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function __invoke(Request $request, $option = 'all'): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'per_page' => ['sometimes', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'recent_search_terms_limit' => ['sometimes', 'integer', 'min:1'],
            'term' => [Rule::requiredIf($option != SearchableOptionsEnum::Recent->value), 'string', 'min:3'],
        ]);

        if ($validation->fails()) {
            return $this->error('Please resolve the warnings!', 400, $validation->errors()->messages());
        }

        try {
            $results = $this->performSearch($option);
        } catch (InvalidSearchableModel $e) {
            Log::error($e->getMessage());
            return $this->error("Unable to search the term.", 400);
        } catch (InvalidSearchableOptionException $e) {
            Log::error($e->getMessage());
            return $this->error("Page Not Found.", 404);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error("An error occured while getting the results.", 400);
        }

        return $this->success('Search results', 200, [
            'results' => $results,
            'options' => SearchableOptionsEnum::_options(),
            'searchable_models' => $this->globalSearchService->getSearchableModels(),
        ]);
    }

    /**
     * Store search history
     * 
     * @authenticated
     * 
     * @bodyParam term string required The search term
     * @bodyParam searchable_id int required The id of the searchable item
     * @bodyParam searchable_type string required The type of the searchable item. Example: App\Models\Region
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function storeSearchHistory(Request $request): JsonResponse
    {
        $input = $request->all();
        $input['searchable_type'] = str_replace('\\\\', '\\', $input['searchable_type']);

        $validation = Validator::make($input, [
            'term' => ['required', 'string', 'min:3'],
            'searchable_id' => ['required', 'integer'],
            'searchable_type' => ['required', 'string', Rule::in(GlobalSearch::SEARCHABLE_MODELS)]
        ]);

        if ($validation->fails()) {
            return $this->error('Please resolve the warnings!', 400, $validation->errors()->messages());
        }

        try {
            $model = $input['searchable_type'];
            $table = (new $model())->getTable();
            $searchable = $model::search($input['term'])->where("$table.id", $request->searchable_id)->first();

            if ($searchable) {
                $searchHistory = $this->globalSearchService->saveSearchHistory($request->term, $searchable);

                return $this->success('Search history saved.', 200, [
                    'search_history' => $searchHistory
                ]);
            } else {
                return $this->error("Unable to find the searchable item.", 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error("An error occured while saving search history.", 400);
        }
    }

    /**
     * Clear search history
     *
     * @authenticated
     * 
     * @bodyParam all boolean required to specify if you want to clear all the search history. Example: true or false
     * @bodyParam ids array specify an array of search history id that you want to delete
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function clearSearchHistory(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'all' => ['required', 'boolean'],
            'ids' => ['required_if:all,false', 'array'],
            'ids.*' => [Rule::exists('search_histories', 'id')]
        ]);

        if ($validation->fails()) {
            return $this->error('Please resolve the warnings!', 400, $validation->errors()->messages());
        }

        try {
            if ($request->all) {
                SearchHistory::filterByUserOrRequestIdentifierToken()->delete();
            } else {
                SearchHistory::filterByUserOrRequestIdentifierToken()->whereIn('id', $request->ids)->delete();
            }

            // CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->error("An error occured while clearing search history.", 400);
        }

        return $this->success('Search history cleared.', 200);
    }

    /**
     * perform Search
     *
     * @param  string $option
     * @return Collection
     */
    private function performSearch(string $option): Collection
    {
        $term = request('term', '');
        $results = collect();

        switch ($option) {
            case SearchableOptionsEnum::Recent->value:
                $results = $this->globalSearchService->getSearchHistories($term);
                //  (new CacheDataManager(
                //     $this->globalSearchService,
                //     'getSearchHistories',
                //     [$term],
                //     false,
                //     true
                // ))->getData();
                break;
            case SearchableOptionsEnum::All->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerAllSearchableModels(),
                    'search',
                    [$term],
                ))->getData();
                break;
            case SearchableOptionsEnum::Events->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerModel(Event::class),
                    'search',
                    [$term],
                ))->getData();
                break;
            case SearchableOptionsEnum::Categories->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerModel(EventCategory::class),
                    'search',
                    [$term],
                ))->getData();
                break;
            case SearchableOptionsEnum::Regions->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerModel(Region::class),
                    'search',
                    [$term],
                ))->getData();
                break;
            case SearchableOptionsEnum::Cities->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerModel(City::class),
                    'search',
                    [$term],
                ))->getData();
                break;
            case SearchableOptionsEnum::Venues->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerModel(Venue::class),
                    'search',
                    [$term],
                ))->getData();
                break;
            case SearchableOptionsEnum::Combinations->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerModel(Combination::class),
                    'search',
                    [$term],
                ))->getData();
                break;
            case SearchableOptionsEnum::Charities->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerModel(Charity::class),
                    'search',
                    [$term],
                ))->getData();
                break;
            case SearchableOptionsEnum::Pages->value:
                $results = (new CacheDataManager(
                    $this->globalSearchService->registerModel(Page::class),
                    'search',
                    [$term],
                ))->getData();
                break;
            default:
                throw InvalidSearchableOptionException::notAValidOption($option);
        }

        if (!empty($term)) {
            $this->globalSearchService->saveSearchHistory($term);
        }

        return $results;
    }
}
