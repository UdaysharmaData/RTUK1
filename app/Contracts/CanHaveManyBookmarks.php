<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyBookmarks
{
    /**
     * @return MorphMany
     */
    public function bookmarks(): MorphMany;
}
