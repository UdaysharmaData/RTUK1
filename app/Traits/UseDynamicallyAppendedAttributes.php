<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait UseDynamicallyAppendedAttributes
{
    protected static array|null $selectedAppendAttributes = null;

    /**
     * Scope a query to remove all appended attributes.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutAppends(Builder $query): Builder
    {
        self::$selectedAppendAttributes = [];

        return $query;
    }

    /**
     * Scope a query to only include the given appended attributes.
     *
     * @param Builder $query
     * @param array $attributes
     * @return Builder
     */
    public function scopeAppendsOnly(Builder $query, array $attributes): Builder
    {
        self::$selectedAppendAttributes = $attributes;

        return $query;
    }

    /**
     * @return string[]
     */
    public function getArrayableAppends(): array
    {
        if (is_array($attributes = self::$selectedAppendAttributes)) {
            $this->setAppends($attributes);
        }

        return $this->getAppends();
    }
}
