<?php

namespace App\Services\GlobalSearchService\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

use App\Models\SearchHistory;
use App\Services\ApiClient\ApiClientSettings;
use App\Services\GlobalSearchService\ModelSearchAspect;
use Illuminate\Support\Facades\Auth;

trait HandleSearchHistory
{

    /**
     * get the search histories
     *
     * @param  string $term
     * @return Collection
     */
    public function getSearchHistories(?string $term = ''): Collection
    {
        $recentSearchTermsLimit = request('recent_search_terms_limit', 10);
        $perPage = request('per_page', 3);

        if (Auth::guard('api')->check() || ApiClientSettings::requestIdentifierToken()) {
            return collect([
                'recent_search_terms' => $this->getRecentSearchTerms($recentSearchTermsLimit),
                'recent_visited_items' => $this->getRecentVisitedItems($term, $perPage),
            ]);
        }

        return collect();
    }

    /**
     * Save search history
     *
     * @param  string $term
     * @param  Model|null $searchable
     * @return SearchHistory|null
     */
    public function saveSearchHistory(string $term, ?Model $searchable = null): ?SearchHistory
    {
        $user = request()->user('api');
        $siteId = clientSiteId();
        $requestIdentifierToken = ApiClientSettings::requestIdentifierToken();

        if ($user || $requestIdentifierToken) { // if the user is authenticated or the request has a valid token 
            $searchHistory = SearchHistory::filterByUserOrRequestIdentifierToken()
                ->firstOrNew([
                    'site_id' => $siteId,
                    'user_id' => $user?->id,
                    'search_term' => $term,
                    'searchable_type' => $searchable?->getMorphClass(),
                    'searchable_id' => $searchable?->id
                ]);

            $searchHistory->touch();

            return $searchHistory;
        }

        return null;
    }

    /**
     * get the recent search terms
     *
     * @param  int $recentSearchTermsLimit
     * @return Collection
     */
    private function getRecentSearchTerms(int $recentSearchTermsLimit): Collection
    {
        return SearchHistory::filterByUserOrRequestIdentifierToken()
            ->whereNull('searchable_type')
            ->select('id', 'search_term')
            ->orderByDesc('updated_at')
            ->limit($recentSearchTermsLimit)
            ->get();
    }

    /**
     * get the recent visited items based on the search histories
     *
     * @param  mixed $user
     * @param  string $term
     * @param  int $perPage
     * @return Collection
     */
    private function getRecentVisitedItems(string $term, int $perPage): Collection
    {
        $recentVisitedItems = collect();

        SearchHistory::filterByUserOrRequestIdentifierToken()->whereNotNull('searchable_type')->select('searchable_type')
            ->groupBy('searchable_type')->get()->each(function ($searchHistory) use ($term, $perPage, $recentVisitedItems) {
                $modelSearchAspect = new ModelSearchAspect($searchHistory->searchable_type, function ($query) {
                    $query->whereHas('searchHistories', function ($query) {
                        $query->filterByUserOrRequestIdentifierToken()
                            ->orderByDesc('updated_at');
                    });
                });

                // register the modelSearchAspect
                $this->registerModelSearchAspect($modelSearchAspect);

                // get the limited results and add the search_history_id
                $results = $modelSearchAspect->getLimitedResults($term, $perPage)->transform(function ($item) {
                    $searchHistory = $item->searchHistories()->filterByUserOrRequestIdentifierToken()->first();
                    return array_merge($item->toArray(), ['search_history_id' => $searchHistory->id]);
                });

                $recentVisitedItems[$modelSearchAspect->getType()] = $results;
            });

        return $recentVisitedItems;
    }
}
