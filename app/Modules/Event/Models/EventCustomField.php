<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\EventCustomFieldTypeEnum;
use App\Enums\EventCustomFieldRuleEnum;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\Relations\EventCustomFieldRelations;

class EventCustomField extends Model
{
    use HasFactory, SoftDeletes, EventCustomFieldRelations, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'event_custom_fields';

    protected $fillable = [
        'event_id',
        'name',
        'slug',
        'placeholder',
        'type',
        'caption',
        'possibilities',
        'status',
        'rule'
    ];

    protected $casts = [
        'type' => EventCustomFieldTypeEnum::class,
        'rule' => EventCustomFieldRuleEnum::class,
        'status' => 'boolean',
        'possibilities' => 'array'
    ];

    protected $appends = [
        'formatted_possibilities',
    ];

    /**
     * Format the possibilities to meet input fields requirements.
     *
     * @return Attribute
     */
    protected function formattedPossibilities(): Attribute
    {
        return Attribute::make(
            get: function () {
                $value = null;

                if ($this->type == EventCustomFieldTypeEnum::Select) {
                    $value = [];

                    foreach ($this->possibilities['options'] as $key => $option) {
                        $value = [...$value, [
                                'option' => $option,
                                'value' => isset($this->possibilities['values'][$key]) ? $this->possibilities['values'][$key] : null
                            ]
                        ];
                    }
                }

                return $value;
            },
        );
    }
}
