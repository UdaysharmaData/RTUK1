<?php

namespace App\Modules\Setting\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Setting\Models\Relations\OrganisationRelations;

class Organisation extends Model
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        OrganisationRelations;

    protected $table = 'organisations';

    protected $fillable = [
        'key',
        'domain',
        'name',
        'code'
    ];

    protected $appends = [
        'url'
    ];

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
}
