<?php

namespace App\Modules\Partner\Models;

use App\Traits\SiteTrait;
use App\Traits\SlugTrait;
use App\Http\Helpers\AccountType;
use App\Traits\Metable\HasOneMeta;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uploadable\HasOneUpload;
use App\Traits\FilterableListQueryScope;
use App\Traits\Socialable\HasManySocials;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Contracts\Metables\CanHaveMetableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Modules\Partner\Models\Relations\PartnerRelations;

class Partner extends Model implements CanHaveUploadableResource, CanHaveMetableResource
{
    use HasFactory,
        SlugTrait,
        SoftDeletes,
        HasOneUpload,
        HasManySocials,
        HasOneMeta,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteTrait,
        PartnerRelations,
        FilterableListQueryScope;

    protected $table = 'partners';

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'website',
        'code',
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the partner permanently will unlink it from partner channels, partner packages and others. This action is irreversible.'
    ];

    public function sluggable(): array
    {
        return [
            'code' => [
                'source' => 'name'
            ],
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    /**
     * Update the name based on the site making the request
     *
     * @return Attribute
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                    return $value . html_entity_decode("&ensp; &#183; &ensp;") . $this->site->name;
                }

                return $value;
            },
        );
    }

    /**
     * The url on the website.
     *
     * @return Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return static::getSite()?->url . "/partners/$this->slug";
            },
        );
    }
}
