<?php

namespace App\Modules\Participant\Models;

use Carbon\Carbon;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InvoiceItemable\HasOneInvoiceItem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\InvoiceItemables\CanHaveInvoiceItemableResource;
use App\Modules\Participant\Models\Relations\FamilyRegistrationRelations;

class FamilyRegistration extends Model implements CanHaveInvoiceItemableResource
{
    use HasFactory, FamilyRegistrationRelations, HasOneInvoiceItem, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'family_registrations';

    protected $fillable = [
        'participant_id',
        'event_custom_field_id',
        'first_name',
        'last_name',
        'gender',
        'dob'
    ];

    protected $appends = [
        'full_name',
        'age'
    ];

    /**
     * Get the full name
     *
     * @return Attribute
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->first_name. ' ' . $this->last_name;
            },
        );
    }

    /**
     * Get the age of the family member
     *
     * @return Attribute
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($this->dob)->age;
            },
        );
    }
}
