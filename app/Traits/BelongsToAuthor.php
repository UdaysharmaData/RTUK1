<?php

namespace App\Traits;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAuthor
{
    /**
     * resource creator/author
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
