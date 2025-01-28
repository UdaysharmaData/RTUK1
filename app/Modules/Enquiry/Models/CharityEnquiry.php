<?php

namespace App\Modules\Enquiry\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Enquiry\Models\Relations\CharityEnquiryRelations;

class CharityEnquiry extends Model
{
    use HasFactory, SoftDeletes, UuidRouteKeyNameTrait, AddUuidRefAttribute, CharityEnquiryRelations;

    protected $table = 'charity_enquiries';

    protected $fillable = [
        'site_id',
        'charity_category_id',
        'name',
        'registration_number',
        'website',
        'address_1',
        'address_2',
        'city',
        'postcode',
        'contact_name',
        'contact_email',
        'contact_phone'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
