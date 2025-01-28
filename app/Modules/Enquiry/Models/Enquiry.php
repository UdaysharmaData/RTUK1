<?php

namespace App\Modules\Enquiry\Models;

use App\Enums\GenderEnum;
use App\Enums\EnquiryActionEnum;
use App\Enums\EnquiryStatusEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FilterableListQueryScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Enquiry\Models\Relations\EnquiryRelations;
use App\Modules\Enquiry\Models\Traits\EnquiryQueryScopeTrait;

class Enquiry extends Model
{
    use HasFactory,
        SoftDeletes,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        EnquiryRelations,
        EnquiryQueryScopeTrait,
        FilterableListQueryScope;

    protected $table = 'enquiries';

    protected $fillable = [
        'site_id',
        'charity_id',
        'event_id',
        'event_category_id',
        'corporate_id',
        'participant_id',
        'external_enquiry_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'postcode',
        'contacted',
        'converted',
        'comments',
        'timeline',
        'custom_charity',
        'fundraising_target',
        'action'
    ];

    protected $casts = [
        'gender' => GenderEnum::class,
        'contacted' => 'boolean',
        'converted' => 'boolean',
        'timeline' => 'array',
        'dob' => 'immutable_date',
        'action' => EnquiryActionEnum::class
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $appends = [
        'full_name',
        'status'
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
                    ? EnquiryStatusEnum::Processed
                    : EnquiryStatusEnum::Pending;
            },
        );
    }
}
