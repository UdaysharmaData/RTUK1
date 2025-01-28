<?php

namespace App\Traits;

use App\Models\Bookmark;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasManyBookmarks
{
    /**
     * @return MorphMany
     */
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }
}
