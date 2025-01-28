<?php

namespace App\Traits;

use App\Modules\Setting\Enums\SiteEnum;
use Illuminate\Support\Str;
use App\Http\Helpers\AccountType;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait ConfigurableEventPropertyNameSlugAttribute
{
    // /**
    //  * The "booted" method of the model.
    //  *
    //  * @return void
    //  */
    // protected static function booted(): void
    // {
    //     static::saving(function ($model) {
    //         $model->slug = Str::slug($model->getAttributes()['name']);
    //     });
    // }

    /**
     * Update the name based on the site making the request
     *
     * @return Attribute
     */
    public function name(): Attribute
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
}
