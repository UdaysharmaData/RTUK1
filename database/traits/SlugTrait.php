<?php

namespace Database\Traits;

use Str;
use Illuminate\Database\Eloquent\Model;

trait SlugTrait
{
    /**
     * Get a unique slug.
     *
     * @param  string    $model
     * @param  string    $string
     * @param  int|null  $id
     * @return string
     */
    protected function getUniqueSlug(string $model, string $string, ?int $id = null): string
    {
        $slug = Str::slug($string);
        $times = 1;

        if (!$this->slugExists($model, $slug, $id))
            return $slug;

        do {
            $_slug = $slug.Str::repeat('_', $times);
            $times++;
        } while($this->slugExists($model, $_slug, $id));

        return $_slug;
    }

    /**
     * Check if the slug exists in the model
     * 
     * @param  string           $model
     * @param  string           $slug
     * @param  int|null         $id
     * @return Model|bool|null
     */
    protected function slugExists(string $model, string $slug, ?int $id = null): Model|bool|null
    {
        if ($id) {
            $exists = $model::where('slug', $slug)->first();
            return $exists ? $exists->id != $id : null;
        }

        return $model::where('slug', $slug)->first();
    }
}