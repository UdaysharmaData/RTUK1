<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uploadable\HasOneUpload;
use App\Enums\EventPagePaymentOptionEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Modules\Event\Models\Relations\EventPageRelations;

class EventPage extends Model implements CanHaveUploadableResource
{
    use HasFactory, HasOneUpload, EventPageRelations, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'event_pages';

    protected $fillable = [
        // 'event_id', // moved to EventEventPage model
        'charity_id',
        'corporate_id',
        'slug',
        'hide_helper',
        'fundraising_title',
        'fundraising_description',
        'fundraising_target',
        'published',
        'code',
        'all_events',
        'fundraising_type',
        'black_text',
        'hide_event_description',
        'reg_form_only',
        'video',
        'use_enquiry_form',
        'payment_option',
        'registration_price',
        'registration_deadline'
    ];

    protected $casts = [
        'hide_helper' => 'boolean',
        'published' => 'boolean',
        'all_events' => 'boolean',
        'hide_event_description' => 'boolean',
        'reg_form_only' => 'boolean',
        'use_enquiry_form' => 'boolean',
        'payment_option' => EventPagePaymentOptionEnum::class,
        'registration_deadline' => 'datetime'
    ];

    /**
     * Check whether a code is already used
     * 
     * @return bool
     */
    public static function codeAlreadyUsed($code): bool
    {
        return static::where('code', $code)->exists();
    }
}
