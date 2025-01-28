<?php

namespace App\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meta extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'meta';

    protected $fillable = [
        'metable_type',
        'metable_id',
        'title',
        'description',
        'keywords',
        'robots',
        'canonical_url'
    ];

    protected $casts = [
        'keywords' => 'array',
        'robots' => 'array',
    ];

    protected $appends = [
        'formatted_keywords',
        'formatted_robots'
    ];

    /**
     * @return MorphTo
     */
    public function metable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Return keywords as a string.
     *
     * @return Attribute
     */
    protected function formattedKeywords(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_array($keywords = $this->keywords)) {
                    return implode(', ', $keywords);
                }

                return $this->keywords;
            },
        );
    }

    /**
     * Return robots as a string.
     *
     * @return Attribute
     */
    protected function formattedRobots(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_array($robots = $this->robots)) {
                    return implode(', ', $robots);
                }

                return $this->robots;
            },
        );
    }
}
