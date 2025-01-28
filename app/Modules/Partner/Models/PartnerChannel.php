<?php

namespace App\Modules\Partner\Models;

use App\Traits\SiteTrait;
use App\Http\Helpers\AccountType;
 
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FilterableListQueryScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Partner\Models\Relations\PartnerChannelRelations;

class PartnerChannel extends Model
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteTrait,
        PartnerChannelRelations,
        FilterableListQueryScope;

    protected $table = 'partner_channels';

    protected $fillable = [
        'partner_id',
        'name',
        'code'
    ];

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
                    return $value . " . " . $this->partner->name;
                }

                return $value;
            },
        );
    }
}
