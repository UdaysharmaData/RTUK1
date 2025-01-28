<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UseSiteGlobalScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AudienceUser extends Pivot
{
    use HasFactory,
        SiteIdAttributeGenerator,
        UseSiteGlobalScope,
        BelongsToSite;
}
