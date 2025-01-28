<?php

namespace App\Modules\Setting\Models;

use App\Traits\SiteTrait;
use Exception;
use Illuminate\Support\Str;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Setting\Models\Relations\SiteRelations;
use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;

class Site extends Model
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteTrait,
        SiteRelations,
        SiteQueryScopeTrait;

    protected $table = 'sites';

    protected $fillable = [
        'key',
        'domain',
        'name',
        'code',
        'status',
        'organisation_id'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    protected $appends = [
        'url'
    ];

    const ACTIVE = 1; // Active site

    const INACTIVE = 0; // InActive site

    /**
     * Get the site's url
     *
     * @return Attribute
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return 'https://' . $this->domain;
            },
        );
    }

    /**
     * Generates cryptographically secure pseudo-random string
     *
     * @param int $length
     * @return string
     * @throws Exception
     */
    public static function generateRandomString(int $length = 16): string
    {
        $bytes = random_bytes($length);
        return bin2hex($bytes);
    }
}
