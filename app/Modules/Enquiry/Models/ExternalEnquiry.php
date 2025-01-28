<?php

namespace App\Modules\Enquiry\Models;

use App\Enums\GenderEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ExternalEnquiryStatusEnum;
use App\Traits\FilterableListQueryScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Enquiry\Models\Relations\ExternalEnquiryRelations;
use App\Modules\Enquiry\Models\Traits\ExternalEnquiryQueryScopeTrait;

class ExternalEnquiry extends Model
{
    use HasFactory,
        SoftDeletes,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        ExternalEnquiryRelations,
        ExternalEnquiryQueryScopeTrait,
        FilterableListQueryScope;

    protected $table = 'external_enquiries';

    protected $fillable = [
        'site_id',
        'charity_id',
        'run_for_charity',
        'event_id',
        'partner_channel_id',
        'event_category_event_third_party_id',
        'channel_record_id',
        'participant_id',
        'origin',
        'contacted',
        'converted',
        'first_name',
        'last_name',
        'email',
        'phone',
        'postcode',
        'address',
        'city',
        'region',
        'country',
        'dob',
        'gender',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'comments',
        'timeline',
        'token',
        'ldt_created_at',
        'ldt_updated_at'
    ];

    protected $casts = [
        'gender' => GenderEnum::class,
        'contacted' => 'boolean',
        'converted' => 'boolean',
        'timeline' => 'array',
        'dob' => 'immutable_date'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'ldt_created_at',
        'ldt_updated_at'
    ];

    protected $appends = [
        'full_name',
        'status'
    ];

    protected $wiith = [
        'participant'
    ];

    /**
     * @return Attribute
     */
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "$this->first_name $this->last_name",
        );
    }

    /**
     * Get the enquiry status
     *
     * @return Attribute
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->participant_id
                    ? ExternalEnquiryStatusEnum::Processed
                    : ExternalEnquiryStatusEnum::Pending;
            },
        );
    }
}
