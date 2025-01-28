<?php

namespace App\Modules\Setting\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\SettingCustomFieldKeyEnum;
use App\Enums\SettingCustomFieldTypeEnum;
use App\Traits\InvoiceItemable\HasManyInvoiceItems;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\InvoiceItemables\CanHaveManyInvoiceItemableResource;

class SettingCustomField extends Model implements CanHaveManyInvoiceItemableResource
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        HasManyInvoiceItems;

    protected $table = 'setting_custom_fields';

    protected $fillable = [
        'setting_id',
        'key',
        'value',
        'type'
    ];

    protected $casts = [
        'type' => SettingCustomFieldTypeEnum::class,
        'key' => SettingCustomFieldKeyEnum::class
    ];

    /**
     * Get the setting associated with the custom setting.
     *
     * @return BelongsTo
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }
}
