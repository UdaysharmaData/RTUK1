<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class MetaKeywordsFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'meta_keywords'
    ];

    /**
     * @param string $keywords
     * @return void
     */
    public function metaKeywords(string $keywords): void
    {
        $this->builder->when(
            $keywords,
            function($query) use ($keywords) {
                $query->whereHas('meta', function (Builder $query) use($keywords) {
                    $items = explode(',', $keywords) ?? [];
                    foreach($items as $key => $item) {
                        $item = strtolower($item);
                        $sqlQuery = "JSON_SEARCH(LOWER(`meta`.`keywords`), 'all', '$item', NULL) IS NOT NULL";
                        $key === 0
                            ? $query->whereRaw($sqlQuery)
                            : $query->orWhereRaw($sqlQuery);
                    }
                });
            }
        );
    }
}
