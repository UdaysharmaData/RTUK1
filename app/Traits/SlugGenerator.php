<?php

namespace App\Traits;

use Str;
use Illuminate\Database\Eloquent\Model;

trait SlugGenerator
{
    /**
     * Get a unique slug.
     *
     * @param  string    $string
     * @param  int|null  $id
     * @param int|null   $siteId
     * @param string $slugColumnName
     * @return string
     */
    protected static function getUniqueSlug(string $string, ?int $id = null, ?int $siteId = null, string $slugColumnName='slug'): string
    {
        $slug = Str::slug($string);
        $times = 1;

        if (!static::slugExists($slug, $id, $siteId))
            return $slug;

        do {
            $_slug = $slug . Str::repeat('-', $times);
            $times++;
        } while (static::slugExists($_slug, $id, $siteId));

        return $_slug;
    }

    /**
     * Check if the slug exists in the model
     * 
     * @param  string           $slug
     * @param  int|null         $id
     * @param int|null          $siteId
     * @param string $slugColumnName
     * @return Model|bool|null
     */
    protected static function slugExists(string $slug, ?int $id = null, ?int $siteId = null, string $slugColumnName = 'slug'): Model|bool|null
    {
        $query = static::where($slugColumnName, $slug)->when($siteId, function($query, $siteId) {
            return $query->where('site_id', $siteId);
        });

        if (method_exists(static::class, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        if ($id) {
            $exists = $query->first();
            return $exists ? $exists->id != $id : null;
        }

        return $query->exists();
    }
}
