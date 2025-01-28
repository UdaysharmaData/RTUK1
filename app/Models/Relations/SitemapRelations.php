<?php

namespace App\Models\Relations;

use App\Traits\BelongsToSite;
use App\Modules\Setting\Models\Site;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait SitemapRelations
{
    use BelongsToSite;
}