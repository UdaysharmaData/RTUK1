<?php

namespace App\Modules\Charity\Models;

use App\Traits\SlugTrait;
use App\Traits\SiteTrait;
use App\Traits\Metable\HasOneMeta;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uploadable\HasManyUploads;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Enquiry\Models\CharityEnquiry;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Contracts\Metables\CanHaveMetableResource;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;

class CharityCategory extends Model implements CanHaveManyUploadableResource, CanHaveMetableResource
{
    use HasFactory,
        SoftDeletes,
        SlugTrait,
        SiteTrait,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        HasManyUploads,
        HasOneMeta;

    protected $table = 'charity_categories';

    protected $fillable = [
        'status',
        'name',
        'color'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the category permanently will unlink it from charities and others. This action is irreversible.'
    ];

    /**
     * The url on the website.
     *
     * @return Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return  static::getSite()?->url . '/charities/categories/' . $this->slug;
            }
        );
    }

    /**
     * Get the charities associated with the category.
     * @return HasMany
     */
    public function charities(): HasMany
    {
        return $this->hasMany(Charity::class);
    }

    /**
     * Get the charity enquiries associated with the category.
     * @return HasMany
     */
    public function charityEnquries(): HasMany
    {
        return $this->hasMany(CharityEnquiry::class);
    }
}
