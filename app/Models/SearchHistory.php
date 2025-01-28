<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\BelongsToSite;
use App\Traits\AddUuidRefAttribute;
use App\Traits\HasAnalyticsMetadata;
use App\Services\ApiClient\ApiClientSettings;

class SearchHistory extends Model
{

    use HasFactory, AddUuidRefAttribute, BelongsToSite, HasAnalyticsMetadata;

    /**
     * @var string[]
     */
    protected $fillable = [
        'site_id',
        'user_id',
        'ref',
        'search_term',
        'searchable_id',
        'searchable_type',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];


    /**
     * searchable
     *
     * @return MorphTo
     */
    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 
     * FIlter the search histories by user or request identifier token
     * 
     * @param   $query
     * @return Builder
     */
    public function scopeFilterByUserOrRequestIdentifierToken(Builder $query): Builder
    {
        $user = request()->user('api');
        $requestIdentifierToken = ApiClientSettings::requestIdentifierToken();

        return $query->where('site_id', clientSiteId())
            ->when((!$user), function ($query) use ($requestIdentifierToken) {
                $query->whereHas('metadata', function ($query) use ($requestIdentifierToken) {
                    $query->where('identifier', $requestIdentifierToken);
                });
            })->when($user, function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
    }
}
