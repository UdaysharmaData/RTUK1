<?php

namespace App\Modules\Participant\Models;

use App\Modules\User\Models\PermissionRole;
use App\Modules\User\Models\PermissionUser;
use App\Modules\User\Models\RoleUser;
use App\Modules\User\Models\SiteUser;
use DB;
use Str;
use Auth;
use Exception;
use App\Mail\Mail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use \Bkwld\Cloner\Cloneable;
use App\Http\Helpers\AccountType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FilterableListQueryScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Events\PartnerCharityAttemptedRegistrationEvent;
use App\Contracts\InvoiceItemables\CanHaveManyInvoiceItemableResource;
use App\Modules\Participant\Models\Relations\ParticipantRelations;
use App\Modules\Participant\Models\Traits\ParticipantQueryScopeTrait;

use App\Modules\Event\Exceptions\IsActiveException;
use App\Modules\Event\Exceptions\HasAvailablePlacesException;
use App\Modules\Participant\Exceptions\IsRegisteredException;

use App\Enums\FeeTypeEnum;
use App\Enums\InvoiceStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\ParticipantStateEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\InvoiceItemStatusEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\EventCustomFieldRuleEnum;
use App\Enums\ParticipantActionTypeEnum;
use App\Enums\CharityMembershipTypeEnum;
use App\Enums\ParticipantPaymentStatusEnum;

use App\Jobs\ResendEmailJob;
use App\Mail\user\UserAccountCreatedByAdminMail;

use App\Traits\SiteTrait;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\InvoiceItemable\HasManyInvoiceItems;

use App\Models\Invoice;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Profile;
use App\Traits\SubjectActivityTrait;
use App\Modules\Charity\Models\Charity;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Participant\Models\Traits\ParticipantDeleteTrait;
use App\Modules\Participant\Models\Traits\ParticipantTransferTrait;

class   Participant extends Model implements CanUseCustomRouteKeyName, CanHaveManyInvoiceItemableResource
{
    use HasFactory,
        ParticipantDeleteTrait,
        SoftDeletes,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        ParticipantRelations,
        SubjectActivityTrait,
        ParticipantQueryScopeTrait,
        HasManyInvoiceItems,
        SiteTrait,
        Cloneable,
        FilterableListQueryScope,
        ParticipantTransferTrait,
        UseDynamicallyAppendedAttributes;


    protected $table = 'participants';

    protected $fillable = [
        'user_id',
        'event_event_category_id',
        'charity_id',
        'corporate_id',
        'status',
        'waive',
        'waiver',
        'state',
        'preferred_heat_time',
        'raced_before',
        'estimated_finish_time',
        'charity_checkout_raised',
        'charity_checkout_title',
        'charity_checkout_status',
        'charity_checkout_created_at',
        'fundraising_target',
        'added_via',
        'event_page_id',
        'enable_family_registration',
        'speak_with_coach',
        'hear_from_partner_charity',
        'reason_for_participating'
    ];

    protected $casts = [
        'status' => ParticipantStatusEnum::class,
        'waive' => ParticipantWaiveEnum::class,
        'waiver' => ParticipantWaiverEnum::class,
        'state' => ParticipantStateEnum::class,
        'raced_before' => 'boolean',
        'charity_checkout_status' => 'boolean',
        'added_via' => ParticipantAddedViaEnum::class,
        'enable_family_registration' => 'boolean',
        'speak_with_coach' => 'boolean',
        'hear_from_partner_charity' => 'boolean'
    ];

    protected $dates = [
        'charity_checkout_created_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $appends = [
        'latest_action',
        'formatted_status',
        'fee_type',
        'payment_status'
    ];

    protected $cloneable_relations = ['participantExtra'];

    public static $actionMessages = [
        'force_delete' => 'Deleting the participant permanently will unlink it from enquiries, external enquiries and others. This action is irreversible.'
    ];

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->attributes['ref'];
    }

    /**
     * Get the latest action value
     *
     * @return Attribute
     */
    protected function latestAction(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $action = $this->participantActions()->with(['user', 'role'])->latest()->first();

                switch ($action?->type) {
                    case ParticipantActionTypeEnum::Added:
                        $value .= "Added by {$action->user->full_name} via " . static::getSite()?->name . "(" . $this->added_via?->formattedName() . ")";
                        break;
                    case ParticipantActionTypeEnum::Deleted:
                        $value .= "Deleted by {$action->user->full_name} on " . $action->created_at?->format("M d, Y");
                        break;
                    case ParticipantActionTypeEnum::Restored:
                        $value .= "Restored by {$action->user->full_name} on" . $action->created_at?->format("M d, Y");
                        break;
                    default:
                        $value = "Added via " . $this->added_via?->formattedName();
                }

                return $value;
            },
        );
    }

    /**
     * Get the formatted status value
     *
     * @return Attribute
     */
    protected function formattedStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $value = (array) $this->status?->name;

                if ($this->paymentStatus) {
                    array_push($value, $this->paymentStatus->name);
                }

                return $value;
            },
        );
    }

    /**
     * Get the age the participant has on the day of the event
     *
     * @return Attribute
     */
    protected function ageOnRaceDay(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // TODO: Compute the participant's age based on the event's date
                return null;
            },
        );
    }

    /**
     * Concatenate the participant, event and event category names
     *
     * @return Attribute
     */
    protected function customName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $this->load(['eventEventCategory.event' => fn ($query) => $query->withTrashed()]);
                return $this->user?->first_name. ' ' .$this->user?->last_name. ' - ' .$this->eventEventCategory?->event->name. ' (' .$this->eventEventCategory?->eventCategory?->name. ')';
            },
        );
    }

    /**
     * Get the fee type (local or international) paid by the participant
     *
     * // TODO: Update this logic
     *
     * @return Attribute
     */
    protected function feeType(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // If an invoice is associated with the participant, compare the price, otherwise, return the local fee type
                $feeType = $this->invoiceItem?->price == $this->eventEventCategory->international_fee
                    ? FeeTypeEnum::International
                    : FeeTypeEnum::Local;

                if ($this->waived) { // When waived, if an invoice is associated with the participant, compare the price, otherwise, return the local fee type
                    $value = $feeType;
                } else {
                    $value = $feeType;
                }

                return $value;
            },
        );
    }

    /**
     * Get the payment status
     *
     * @return Attribute
     */
    protected function paymentStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->waive && $this->waiver) {
                    $value = ParticipantPaymentStatusEnum::Waived;
                } else if ($this->invoiceItem && $this->invoiceItem->invoice) { // TODO: Load soft deleted invoice and consider it as existing
                    $value = $this->invoiceItem->status;
                } else {
                    $value = InvoiceItemStatusEnum::Unpaid;
                }

                return $value;
            },
        );
    }

    /**
     * Check if the participant has fulfilled the criteria to be considered among the completed participants for the event
     *
     * @return bool
     */
    public function isConsideredAmongCompletedParticipants(): bool
    {
        return ($this->status != ParticipantStatusEnum::Transferred) && ($this->status == ParticipantStatusEnum::Complete || ($this->waive == ParticipantWaiveEnum::Completely && $this->waiver == ParticipantWaiverEnum::Partner) || ($this->invoiceItem?->invoice?->status == InvoiceStatusEnum::Paid /* && $this->invoiceItem?->invoice?->charge_id*/)); // TO ASK: Request for RunThrought and RunForCharity specific logic for this. NB: The current logic suits Runthrough
    }

    /**
     * Format the path through which the participant registered.
     *
     * @param  Participant $participant
     * @return ?string
     */
    public static function getAddedVia(Participant $participant): ?string
    {
        $addedThrough = null;

        $addedThrough = in_array($participant->added_via->name, [ParticipantAddedViaEnum::RegistrationPage->name, ParticipantAddedViaEnum::PartnerEvents->name])
            ? '['.trim(preg_replace('/([A-Z])/', ' $1', $participant->added_via->name)).']: '
            : $participant->added_via->name;

        if (($participant->added_via == ParticipantAddedViaEnum::RegistrationPage && $participant->eventPage) || ($participant->added_via == ParticipantAddedViaEnum::PartnerEvents /*&& $participant->addedByUser*/)) {

            if ($participant->added_via == ParticipantAddedViaEnum::RegistrationPage) {

                $addedThrough = $addedThrough.substr($participant->eventPage->url, 2);

            } else if ($participant->added_via == ParticipantAddedViaEnum::PartnerEvents) {

                $participantActions = $participant->participantActions;

                foreach ($participantActions as $participantAction) {
                    $addedThrough .= "[{$participantAction->type->name} : {$participantAction->user?->full_name} ({$participantAction->role?->name->formattedName()}) ]";
                }
            }
        }

        return $addedThrough;
    }

    /**
     * Validate event reg fields (Fields required by the event)
     *
     * @param  Participant  $participant
     * @return array
     */
    public static function validateRegFields(Participant $participant): array
    {
        $participant->refresh(); // Reload the current model instance with fresh attributes from the database

        $event = $participant->eventEventCategory->event;

        $eventRegRequiredFields = collect($event)->filter(function ($value, $key) { // Get the registration/required fields for the event.
            return Str::startsWith($key, 'reg_') && $value;
        })->keys()->all();

        $value = true; // Initialize the check result to true
        $errors = []; //  The array of errors

        if ($participant->participantExtra) {
            self::validateParticipantExtraProfileRegFields($participant, $eventRegRequiredFields, $value, $errors);
        } else {
            self::validateUserRegFields($participant, $eventRegRequiredFields, $value, $errors);
        }

        if (in_array('reg_email', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->email;
            static::setFieldError($errors, $filled, 'email');
            $value *= $filled;
        }

        if (in_array('reg_state', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->region;
            static::setFieldError($errors, $filled, 'state');
            $value *= $filled;
        }

        if (in_array('reg_city', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->city;
            static::setFieldError($errors, $filled, 'city');
            $value *= $filled;
        }

        if (in_array('reg_country', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->country;
            static::setFieldError($errors, $filled, 'country');
            $value *= $filled;
        }

        if (in_array('reg_postcode', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->postcode;
            static::setFieldError($errors, $filled, 'postcode');
            $value *= $filled;
        }

        if (in_array('reg_address', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->address;
            static::setFieldError($errors, $filled, 'address');
            $value *= $filled;
        }

        if (in_array('reg_nationality', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->nationality;
            static::setFieldError($errors, $filled, 'nationality');
            $value *= $filled;
        }

        if (in_array('reg_occupation', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->occupation;
            static::setFieldError($errors, $filled, 'occupation');
            $value *= $filled;
        }

        if (in_array('reg_passport_number', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->passport_number;
            static::setFieldError($errors, $filled, 'passport_number');
            $value *= $filled;
        }

        if (in_array('reg_month_born_in', $eventRegRequiredFields)) { // This value is gotten from the dob through an accessor
            $filled = (bool) $participant->user->profile?->month_born_in;
            static::setFieldError($errors, $filled, 'dob', 'date of birth');
            $value *= $filled;
        }

        // if (in_array('reg_minimum_age', $eventRegRequiredFields)) {                          // TODO: This field is not supposed to be a reg field. It is similar to the born_before field. Update the schema and rename it to minimum_age
        //     $filled = (bool) ($participant->user->profile?->age > $event->reg_minimum_age);
        //     static::setFieldError($errors, $filled, 'dob');
        //     $value *= $filled;
        // }

        if (in_array('reg_emergency_contact_name', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->participantProfile?->emergency_contact_name;
            static::setFieldError($errors, $filled, 'emergency_contact_name');
            $value *= $filled;
        }

        if (in_array('reg_emergency_contact_phone', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->participantProfile?->emergency_contact_phone;
            static::setFieldError($errors, $filled, 'emergency_contact_phone');
            $value *= $filled;
        }

        if (in_array('reg_tshirt_size', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->participantProfile?->tshirt_size;
            static::setFieldError($errors, $filled, 'tshirt_size');
            $value *= $filled;
        }

        if (in_array('reg_preferred_heat_time', $eventRegRequiredFields)) {
            $filled = (bool) $participant->preferred_heat_time;
            static::setFieldError($errors, $filled, 'preferred_heat_time');
            $value *= $filled;
        }

        if (in_array('reg_estimated_finish_time', $eventRegRequiredFields)) {
            $filled = (bool) $participant->estimated_finish_time;
            static::setFieldError($errors, $filled, 'estimated_finish_time');
            $value *= $filled;
        }

        if (in_array('reg_raced_before', $eventRegRequiredFields)) { // This field will always return true though (since it is boolean)
            $filled = (bool) isset($participant->raced_before);
            static::setFieldError($errors, $filled, 'raced_before');
            $value *= $filled;
        }

        if (in_array('reg_reason_for_participating', $eventRegRequiredFields)) {
            $filled = (bool) $participant->reason_for_participating;
            static::setFieldError($errors, $filled, 'reason_for_participating');
            $value *= $filled;
        }

        if (in_array('reg_hear_from_partner_charity', $eventRegRequiredFields)) { // This field will always return true though (since it is boolean)
            $filled = (bool) isset($participant->hear_from_partner_charity);
            static::setFieldError($errors, $filled, 'hear_from_partner_charity');
            $value *= $filled;
        }

        if (in_array('reg_speak_with_coach', $eventRegRequiredFields)) { // This field will always return true though (since it is boolean)
            $filled = (bool) isset($participant->speak_with_coach);
            static::setFieldError($errors, $filled, 'speak_with_coach');
            $value *= $filled;
        }

        if (in_array('reg_family_registrations', $eventRegRequiredFields)) { // Validate family registraion fields

        }

        return $value
            ? [
                'status' => true,
                'message' => 'Successfully passed required fields validation!',
                'errors' => $errors
            ]
            : [
                'status' => false,
                'message' => array_values($errors)[0],
                'errors' => $errors
            ];
    }

    /**
     * Validate event custom fields (Custom fields required by the event)
     *
     * @param  Participant  $participant
     * @return array
     */
    public static function validateCustomFields(Participant $participant): array
    {
        $value = true;
        $errors = [];

        foreach ($participant->eventEventCategory->event->eventCustomFields as $field) { // Validate custom fields.
            $customField = $participant->participantCustomFields()
                ->where('event_custom_field_id', $field->id)
                ->value('value');

            if (! isset($customField) && $field->rule == EventCustomFieldRuleEnum::Required) { // Ensure the participant has filled values for the required custom fields
                $filled = false;
                static::setFieldError($errors, $filled, $field->slug, Str::lower($field->name));
                $value *= $filled;
            }
        }

        return $value
            ? [
                'status' => true,
                'message' => 'Successfully passed custom fields validation!',
                'errors' => $errors
            ]
            : [
                'status' => false,
                'message' => array_values($errors)[0],
                'errors' => $errors
            ];
    }

    /**
     * Set an error message to the field name that did not pass the validation
     *
     * @param  array   $errors   The array of fields that did not pass validation
     * @param  bool    $filled   Whether the value is filled or not
     * @param  string  $name     The field name
     * @param  ?string $label   The field label
     * @return void
     */
    private static function setFieldError(array &$errors, bool $filled, string $name, ?string $label=null)
    {
        if (! $filled) {
            $errors = [
                ...$errors,
                "{$name}" => [
                    "The ".($label ?? \Str::replace('_', ' ', $name))." field is required."
                ]
            ];
        }
    }

    /**
     * Validate event reg fields (Fields required by the event) - For participant extra
     *
     * @param  Participant  $participant
     * @param  array        $eventRegRequiredFields
     * @param  bool         $value
     * @param  array        $errors
     * @return void
     */
    public static function validateParticipantExtraProfileRegFields(Participant $participant, array $eventRegRequiredFields, bool &$value, array &$errors): void
    {
        if (in_array('reg_first_name', $eventRegRequiredFields)) {
            $filled = (bool) $participant->participantExtra->first_name;
            static::setFieldError($errors, $filled, 'first_name');
            $value *= $filled;
        }

        if (in_array('reg_last_name', $eventRegRequiredFields)) {
            $filled = (bool) $participant->participantExtra->last_name;
            static::setFieldError($errors, $filled, 'last_name');
            $value *= $filled;
        }

        if (in_array('reg_phone', $eventRegRequiredFields)) {
            $filled = (bool) $participant->participantExtra->phone;
            static::setFieldError($errors, $filled, 'phone');
            $value *= $filled;
        }

        if (in_array('reg_gender', $eventRegRequiredFields)) {
            $filled = (bool) $participant->participantExtra->gender;
            static::setFieldError($errors, $filled, 'gender');
            $value *= $filled;
        }

        if (in_array('reg_dob', $eventRegRequiredFields)) {
            $filled = (bool) $participant->participantExtra->dob;
            static::setFieldError($errors, $filled, 'dob', 'date of birth');
            $value *= $filled;
        }

        if (in_array('reg_ethnicity', $eventRegRequiredFields)) {
            $filled = (bool) $participant->participantExtra->ethnicity;
            static::setFieldError($errors, $filled, 'ethnicity');
            $value *= $filled;
        }

        if (in_array('reg_weekly_physical_activity', $eventRegRequiredFields)) { // This field will always return true though (since it is boolean)
            $filled = (bool) $participant->participantExtra->weekly_physical_activity;
            static::setFieldError($errors, $filled, 'weekly_physical_activity');
            $value *= $filled;
        }
    }

    /**
     * Validate event reg fields (Fields required by the event) - For main record (participant)
     *
     * @param  Participant  $participant
     * @param  array        $eventRegRequiredFields
     * @param  bool         $value
     * @param  array        $errors
     * @return void
     */
    public static function validateUserRegFields(Participant $participant, array $eventRegRequiredFields, bool &$value, array &$errors): void
    {
        if (in_array('reg_first_name', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->first_name;
            static::setFieldError($errors, $filled, 'first_name');
            $value *= $filled;
        }

        if (in_array('reg_last_name', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->last_name;
            static::setFieldError($errors, $filled, 'last_name');
            $value *= $filled;
        }

        if (in_array('reg_phone', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->phone;
            static::setFieldError($errors, $filled, 'phone');
            $value *= $filled;
        }

        if (in_array('reg_gender', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->gender;
            static::setFieldError($errors, $filled, 'gender');
            $value *= $filled;
        }

        if (in_array('reg_dob', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->dob;
            static::setFieldError($errors, $filled, 'dob', 'date of birth');
            $value *= $filled;
        }

        if (in_array('reg_ethnicity', $eventRegRequiredFields)) {
            $filled = (bool) $participant->user->profile?->ethnicity;
            static::setFieldError($errors, $filled, 'ethnicity');
            $value *= $filled;
        }

        if (in_array('reg_weekly_physical_activity', $eventRegRequiredFields)) { // This field will always return true though (since it is boolean)
            $filled = (bool) $participant->user->profile?->participantProfile?->weekly_physical_activity;
            static::setFieldError($errors, $filled, 'weekly_physical_activity');
            $value *= $filled;
        }
    }

    /**
     * Check whether the user is registered for the event event category.
     *
     * @param  User                $user
     * @param  EventEventCategory  $eec
     * @return object
     */
    public static function isRegisteredToEEC(User $user, EventEventCategory $eec): object
    {
        $participant = static::with(['event' => function ($query) {
            $query->withTrashed();
        }])->where('user_id', $user->id)
            ->where('event_event_category_id', $eec->id)
            ->where('status', '!=','transferred');

        if ($participant->doesntExist()) { // In case the user is not already registered for the event under this category
            return (object) [
                'status' => false,
                'message' => 'The user is not registered under this category.'
            ];
        }

        $participant = $participant->first();

        $isParticipant = AccountType::isParticipant(); // Used to customize the message returned

        if ($participant->status == ParticipantStatusEnum::Complete) { // In case the participant have completed their registration
            return (object) [
                'status' => true,
                'message' => $isParticipant
                    ? 'You are already registered for the event ' . $participant->event->formattedName . ' ('. $participant->eventEventCategory?->eventCategory->name .'). You have completed your registration.'
                    : 'The user is already registered for the event ' . $participant->event->formattedName . ' ('. $participant->eventEventCategory?->eventCategory->name .'). They have completed their registration.'
            ];
        }

        if (($participant->status != ParticipantStatusEnum::Complete) && ($participant->waive == ParticipantWaiveEnum::Completely || $participant->waive == ParticipantWaiveEnum::Partially)) { // In case the waiver pays for the participant's registration and the participant has not yet completed his/her registration
            return (object) [
                'status' => true,
                'message' => $isParticipant
                    ? "You are already registered for the event " . $participant->event->formattedName . " (". $participant->eventEventCategory?->eventCategory->name ."). You have been {$participant->waive->value} waived by {$participant->waiver->name} (". static::getWaiver($participant, $participant->waiver) .") and have not yet completed your registration."
                    : "The user is already registered for the event " . $participant->event->formattedName . " (". $participant->eventEventCategory?->eventCategory->name ."). They are {$participant->waive->value} waived by {$participant->waiver->name} (". static::getWaiver($participant, $participant->waiver) .") and have not yet completed their registration."
            ];
        }

        if (! $participant->invoiceItem || ($participant->invoiceItem && $participant->invoiceItem->invoice->status == InvoiceStatusEnum::Unpaid)) { // In case the participant has not yet paid for his place
            return (object) [
                'status' => true,
                'message' => $isParticipant
                    ? 'You are already registered for the event ' . $participant->event->formattedName . ' ('. $participant->eventEventCategory?->eventCategory->name .'). You have not yet paid nor completed your registration.'
                    : 'The user is already registered for the event ' . $participant->event->formattedName . ' ('. $participant->eventEventCategory?->eventCategory->name .'). They have not yet paid nor completed their registration.'
            ];
        }

        return (object) [
            'status' => true,
            'message' => $isParticipant
                ? 'You are already registered for the event ' . $participant->event->formattedName . ' (' . ($participant->eventEventCategory?->eventCategory?->name ?? 'N/A') . '). You have paid but have not yet completed your registration.'
                : 'The user is already registered for the event ' . $participant->event->formattedName . ' (' . ($participant->eventEventCategory?->eventCategory?->name ?? 'N/A') . '). They have paid but have not yet completed their registration.'
        ];
    }

    /**
     * Get the waiver's name.
     *
     * @param  Participant                 $participant
     * @param  null|ParticipantWaiverEnum  $value
     * @return string|null
     */
    public static function getWaiver(Participant $participant, ?ParticipantWaiverEnum $value): ?string
    {
        switch ($value) {
            case ParticipantWaiverEnum::Charity:
                $value = $participant->charity?->name;
                break;

            case ParticipantWaiverEnum::Partner:
                $value = $participant->externalEnquiry?->partnerChannel?->partner?->name;
                break;

            case ParticipantWaiverEnum::Corporate:
            default:
                $value = $value?->name;
        }

        return $value;
    }

    /**
     * Register a participant for an event
     *
     * @param  Request                  $request
     * @param  EventEventCategory       $eec
     * @param  ParticipantAddedViaEnum  $addedVia
     * @param  User|null                $user
     * @param  bool                     $forceDuplicate // Forces a double registration for a given event and event category
     * @return object
     */
    public static function registerForEvent(Request $request, EventEventCategory $eec, ParticipantAddedViaEnum $addedVia = ParticipantAddedViaEnum::PartnerEvents, ?User $user = null, bool $forceDuplicate = false): object
    {
        // CHECK IF THE PARTICIPANT CAN REGISTER
        $regActive = $eec->registrationActive($request); // Places can only be offered to events whose registrations are still active

        if (! $regActive->status) { // Check if registrations are still active
            throw new IsActiveException($regActive->message);
        }

        if (AccountType::isCharityOwnerOrCharityUser()) {
            $charity = Auth::user()->charityUser->charity;
        } else if ($request->filled('charity')) {
            $charity = Charity::where('ref', $request->charity)->first();
        }

        $hasAvailablePlaces = $eec->_hasAvailablePlaces($request, $charity ?? null);

        if (! $hasAvailablePlaces->status) {
            if (isset($charity) && $charity->latestCharityMembership?->type == CharityMembershipTypeEnum::Partner) { // Partner charities are only entitled to one registration for all events per year
                event(new PartnerCharityAttemptedRegistrationEvent($charity, $eec->event, $request->all()));
            }

            throw new HasAvailablePlacesException($hasAvailablePlaces->message);
        }

        $userData = static::CheckByEmail($request->email);

        // REGISTER THE PARTICIPANT
        if (!$user && !$userData) { // Only create the user when they were not passed
            $createTheUser = static::createTheUser($request, $addedVia, $charity ?? null); // Create the user
        } else {
            $createTheUser = new \stdClass;
            $createTheUser->user = $user ?? $userData->user;
        }

        if (!$createTheUser->user?->wasRecentlyCreated) { // Check if the user is registered for the event under this category. NB: This is only checked when the user is not newly created
            $isUserRegistered = static::isUserRegistered($createTheUser->user, $eec, $forceDuplicate);
            $createTheUser->isDoubleRegistration = $isUserRegistered?->isDoubleRegistration ?? false;
        }

        $createTheParticipant = static::createTheParticipant($request, $createTheUser->user, $eec, $addedVia, $charity ?? null); // Create the participant
        
        if (AccountType::isAdminOrAccountManagerOrCharityOwnerOrCharityUserOrDeveloper() || app()->runningInConsole()) { // Only the admin, account manager or charity (owner & user) can fill these fields
            $paymentStatus = static::savePaymentStatus($request, $createTheParticipant->participant);
        }

        return (object) [
            'status' => true,
            'message' => "Registration Successful!",
            '_message' => isset($paymentStatus) ? $paymentStatus?->_message : null,
            'participant' => $createTheParticipant->participant,
            'forceDuplicate' => $forceDuplicate,
            'isDoubleRegistration' => $createTheUser->isDoubleRegistration ?? false,
            'user' => $createTheUser->user,
            'wasRecentlyCreated' => $createTheUser->wasRecentlyCreated ?? false
        ];
    }

    public static function registerForSingleEvent(Request $request, EventEventCategory $eec, ParticipantAddedViaEnum $addedVia = ParticipantAddedViaEnum::PartnerEvents, ?User $user = null, bool $forceDuplicate = false): object
    {

        // CHECK IF THE PARTICIPANT CAN REGISTER
        $regActive = $eec->registrationSingleActive($request); // Places can only be offered to events whose registrations are still active

        if (! $regActive->status) { // Check if registrations are still active
            throw new IsActiveException($regActive->message);
        }

        if (AccountType::isCharityOwnerOrCharityUser()) {
            $charity = Auth::user()->charityUser->charity;
        } else if ($request->filled('charity')) {
            $charity = Charity::where('ref', $request->charity)->first();
        }

        $hasAvailablePlaces = $eec->_hasAvailablePlaces($request, $charity ?? null);

        if (! $hasAvailablePlaces->status) {
            if (isset($charity) && $charity->latestCharityMembership?->type == CharityMembershipTypeEnum::Partner) { // Partner charities are only entitled to one registration for all events per year
                event(new PartnerCharityAttemptedRegistrationEvent($charity, $eec->event, $request->all()));
            }

            throw new HasAvailablePlacesException($hasAvailablePlaces->message);
        }

        $userData = User::where('email', $request->email)->first();

        // REGISTER THE PARTICIPANT
        if (! $user && ! $userData) { // Only create the user when they were not passed
            $createTheUser = static::createTheUser($request, $addedVia, $charity ?? null); // Create the user
        }else {
            $createTheUser = new \stdClass;
            $createTheUser->user = $user;
            $createTheUser = $userData;
        }

        if (! $createTheUser->user?->wasRecentlyCreated) { // Check if the user is registered for the event under this category. NB: This is only checked when the user is not newly created
            $isUserRegistered = static::isUserSingleRegistered($userData, $eec, $forceDuplicate);
            $createTheUser->isDoubleRegistration = $isUserRegistered?->isDoubleRegistration ?? false;
        }

        if(!$userData){
            $createTheParticipant = static::createTheParticipant($request, $createTheUser->user, $eec, $addedVia, $charity ?? null); // Create the participant
        }else{
            $createTheParticipant = static::createTheParticipant($request, $userData, $eec, $addedVia, $charity ?? null); // Create the participant
        }

        if (AccountType::isAdminOrAccountManagerOrCharityOwnerOrCharityUserOrDeveloper() || app()->runningInConsole()) { // Only the admin, account manager or charity (owner & user) can fill these fields
            $paymentStatus = static::savePaymentStatus($request, $createTheParticipant->participant);
        }
     //   $participant = Participant::where('user_id',$userData->id)->first();

        return (object) [
            'status' => true,
            'message' => "Registration Successful!",
            '_message' => isset($paymentStatus) ? $paymentStatus?->_message : null,
            'participant' => $createTheParticipant->participant,
            'forceDuplicate' => $forceDuplicate,
            'isDoubleRegistration' => $createTheUser->isDoubleRegistration ?? false,
            'user' => $createTheUser->user,
            'wasRecentlyCreated' => $createTheUser->wasRecentlyCreated ?? false
        ];
    }

    /**
     * Check whether the user is registered for the event event category.
     *
     * @param  User                  $user
     * @param  EventEventCategory    $eec
     * @param  bool                  $forceDuplicate // Forces a double registration for a given event and event category
     * @return object
     */
    public static function isUserRegistered(User $user, EventEventCategory $eec, bool $forceDuplicate = false): object
    {
        // Check if the user has already registered for the event under this category
        $isRegistered = self::isRegisteredToEEC($user, $eec);

        if ($isRegistered->status && !$forceDuplicate) // Ensure participants register under an event's category once unless they insist to register more than once
            throw new IsRegisteredException($isRegistered->message);

        if ($isRegistered->status && $forceDuplicate) // Check if this is a double registration
            $isDoubleRegistration = true;

        return (object) [
            'status' => $isDoubleRegistration ?? false,
            'message' => isset($isDoubleRegistration) ? 'Double registration!' :  "The user is not registered for the event!",
            'isDoubleRegistration' => $isDoubleRegistration ?? false
        ];
    }

    public static function isUserSingleRegistered($user, EventEventCategory $eec, bool $forceDuplicate = false): object
    {
        // Check if the user has already registered for the event under this category
        $isRegistered = self::isRegisteredToEEC($user, $eec);

        if ($isRegistered->status && !$forceDuplicate) // Ensure participants register under an event's category once unless they insist to register more than once
            throw new IsRegisteredException($isRegistered->message);

        if ($isRegistered->status && $forceDuplicate) // Check if this is a double registration
            $isDoubleRegistration = true;

        return (object) [
            'status' => $isDoubleRegistration ?? false,
            'message' => isset($isDoubleRegistration) ? 'Double registration!' :  "The user is not registered for the event!",
            'isDoubleRegistration' => $isDoubleRegistration ?? false
        ];
    }
    /**
     * [registerForEvent] Create the user
     *
     * @param  Request                  $request
     * @param  ParticipantAddedViaEnum  $addedVia
     * @param  Charity|null             $charity
     * @return object
     */
    public static function createTheUser(Request $request, ParticipantAddedViaEnum $addedVia, ?Charity $charity = null): object
    {
        $user = User::firstOrNew(['email' => $request->email]);

        if ($user->exists) {
            $user->bootstrapUserRelatedProperties(); // Assign the participant role and associated permissions if the user doesn't have them.

            $routes = [
                "api/v1/portal/enquiries/external/{ref}/place/offer",
                "api/v1/portal/enquiries/{ref}/place/offer",
                "api/v1/portal/participants/{participant}/place/offer"
            ];

            if (!app()->runningInConsole() && (Route::getFacadeRoot()?->current()?->uri() && !in_array(Route::getFacadeRoot()?->current()?->uri(), $routes))) { // Don't update these when offering places from LDT Fetch due to participant extra or through the offer place methods of the participant, enquiry or external enquiry controllers
                if ($request->filled('first_name') || $request->filled('last_name')) {
                    $user->update($request->only(['first_name', 'last_name']));
                }
            }

            if (!$user->hasAccess) {
                throw new \Exception('The user\'s access was restricted!');
            }

            if ($charity && (! $user->charityUser()->where('type', CharityUserTypeEnum::Participant)->first() || ($request->filled('make_default') && $request->make_default))) { // Change the user's (participant) default charity or assign the current one to them if they don't have one.
                if ($request->filled('make_default') && $request->make_default) {
                    $charityIds = $user->charities() // Get the default charity (can be more than one by mistake) the user has a relationship with as a participant.
                        ->where('charity_user.type', CharityUserTypeEnum::Participant)
                        ->pluck('charities.id');

                    if ($charityIds->count()) { // Detach the participant relationship the user has with the charity
                        $user->charities()->detach($charityIds);
                    }
                }

                $user->charities()->syncWithoutDetaching([$charity->id => ['type' => CharityUserTypeEnum::Participant]]); // Attach the user to its new default charity.
            }

            if ($request->filled('profile')) { // Update the user's profile
                static::createOrUpdateUserProfile($request, $user);
            }
        } else if (User::where('email', $request->email)->withTrashed()->first()) { // In case the user was soft deleted
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->info("Registration attempt: The user was soft deleted!"); // Notify the admin for they to manually offer a place to the user after taking the decision whether or not to restore or delete and recreate the user.
            // TODO: Notify the admin via email for they to manually offer a place to the user after taking the decision whether or not to restore or delete and recreate the user.
            throw new \Exception('The user was soft deleted!');
        } else {
            $user->fill($request->only(['first_name', 'last_name', 'phone']));

            if ($request->filled('customer')) { // Save stripe customer id
                $user->stripe_customer_id = $request->customer;
            }

            $user->save();

            $password = static::setGeneratedPasswordAndNotifyUser($user);

            if ($charity) { // Assign the charity to the user (as its default charity)
                $user->charities()->syncWithoutDetaching([$charity->id => ['type' => CharityUserTypeEnum::Participant]]);
            }

            if ($request->filled('profile')) { // Create user profile
                static::createOrUpdateUserProfile($request, $user);
            }

            if (app()->runningInConsole()) { // TODO: Runthrough Deployment - Uncomment this during deployment
                try {
                    if (env('MAIL_USERNAME') == "0f3e3d19a8d0fb") { // Avoid sending emails to the mailtrap account production@sports-techsolutions.com
                        Log::channel(static::getSite()?->code . 'ldtoffer')->debug('New Account Created Via LDT Fetch: '. $user->email);
                    } else {
                        try {
                            if ($request->ldt_created_at && $request->ldt_created_at->toDateString() >= Carbon::now()->toDateString()) {
                            Mail::site()->send(new UserAccountCreatedByAdminMail($user, $password)); // Notify the user about account creation
                            } else {
                                Log::channel(static::getSite()?->code . 'ldtoffer')->info("No email sent");

                            }
                        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                            Log::channel(static::getSite()?->code . 'mailexception')->info("Participants - New Registration");
                            Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                            dispatch(new ResendEmailJob(new UserAccountCreatedByAdminMail($user, $password), clientSite()));
                        } catch (\Exception $e) {
                            Log::channel(static::getSite()?->code . 'mailexception')->info("Participants - New Registration");
                            Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                            dispatch(new ResendEmailJob(new UserAccountCreatedByAdminMail($user, $password), clientSite()));
                        }
                    }
                } catch (Exception $e) { // Issues at the level of the email are less dangerous as the process should have completed
                    Log::channel(static::getSite()?->code . 'stripecharge')->info("Participant Registration Exception. Unable to process participant registration mail. " . $e->getMessage());
                    Log::channel(static::getSite()?->code . 'adminanddeveloper')->info("Participant Registration Exception. Unable to process participant registration mail. " . $e->getMessage());
                    Log::channel(static::getSite()?->code . 'adminanddeveloper')->info($e);
                }
            } else {
                Log::channel(static::getSite()?->code . 'ldtoffer')->debug('New Account Created Via LDT Fetch: '. $user->email);
            }
        }

        return (object) [
            'status' => true,
            'message' => "User creation successful!",
            'user' => $user,
            'wasRecentlyCreated' => $user->wasRecentlyCreated
        ];
    }

    /**
     * [registerForEvent] Set the generated password for the newly created user
     *
     * @param  User    $user
     * @return string
     */
    public static function setGeneratedPasswordAndNotifyUser(User $user): string
    {
        $user->temp_pass = 1;
        $specialChars = ['*', '&', '#', '=', '!'];
        $password = Str::random(10) . $specialChars[array_rand($specialChars, 1)];
        $user->password = \Hash::make($password);
        $user->save();

        return $password;
    }

    /**
     * [registerForEvent] Create the user profile
     *
     * @param  Request                                  $request
     * @param  User                                     $user
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function createOrUpdateUserProfile(Request $request, User $user): \Illuminate\Database\Eloquent\Model|null
    {
        if ($request->filled('profile')) {
            $data = array_filter($request->profile, function ($val) { // Remove all null keys from the array
                return !is_null($val);
            });

            if (! empty($data)) {
                $profile = $user->profile()->updateOrCreate([], $data);

                static::createOrUpdateUserParticipantProfile($request, $profile);

                return $profile;
            }
        }

        return null;
    }

    /**
     * [registerForEvent] Create the participant profile
     *
     * @param  Request                                  $request
     * @param  Profile                                  $profile
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function createOrUpdateUserParticipantProfile(Request $request, Profile $profile): \Illuminate\Database\Eloquent\Model|null
    {
        if ($request->filled('profile.participant_profile')) {
            $data = array_filter($request->profile['participant_profile'], function ($val) { // Remove all null keys from the array
                return !is_null($val);
            });

            if (! empty($data)) {
                return $profile->participantProfile()->updateOrCreate([], $data);
            }
        }

        return null;
    }

    /**
     * [registerForEvent] Create the participant
     *
     * @param  Request                  $request
     * @param  User                     $user
     * @param  EventEventCategory       $eec
     * @param  ParticipantAddedViaEnum  $addedVia
     * @param  Charity|null             $charity
     * @return object
     */
    public static function createTheParticipant(Request $request, User $user, EventEventCategory $eec, ParticipantAddedViaEnum $addedVia, ?Charity $charity = null): object
    {
        $participant = new Participant();
        $participant->user_id = $user->id;

        $participant->event_event_category_id = $eec->id;

        if ($charity) {
            $participant->charity_id = $charity->id;
        }

        // Set some data
        $participant->status = ParticipantStatusEnum::Notified; // Set the status to notified since the user's registration got initiated by another user (admin or account_manager or charity).
        $participant->added_via = $addedVia;

        if ($request->filled('participant')) {
            $participant->raced_before = $request->participant['raced_before'];
            $participant->estimated_finish_time = $request->participant['estimated_finish_time'];
            $participant->speak_with_coach = $request->participant['speak_with_coach'];
            $participant->hear_from_partner_charity = $request->participant['hear_from_partner_charity'];
            $participant->reason_for_participating = $request->participant['reason_for_participating'];
        }

        $participant->save();

        if (Auth::check()) { // Only save this for authenticated requests
            $participant->participantActions()->create([
                'type' => ParticipantActionTypeEnum::Added,
                'user_id' => Auth::user()->id,
                'role_id' => Auth::user()->activeRole?->role_id
            ]);
        }

        return (object) [
            'status' => true,
            'message' => "Participant creation successful!",
            'participant' => $participant->load('user.profile.participantProfile')
        ];
    }

    /**
     * [registerForEvent] Save participant payment status
     *
     * @param  Request       $request
     * @param  Participant   $participant
     * @return object
     */
    public static function savePaymentStatus(Request $request, Participant &$participant): object
    {
        if ($request->filled('payment_status')) {
            if ($request->filled('waive') && $request->filled('waiver')) { // Waive must be set whenever the participant is exempted (particially or fully) from payment.
                $participant->waive = $request->waive;
                $participant->waiver = $request->waiver;
            } else if ($request->payment_status == ParticipantPaymentStatusEnum::Paid->value) { // Create a paid invoice and attach it to the participant (invoiceItem)
                if (! (AccountType::isAdmin())) { // Only the admin can set the payment status to paid
                    throw new \Exception("Only the admin can set the payment status to paid");
                }

                $invoice = $participant->user->invoices()->create([
                    'name' => Invoice::getFormattedName(InvoiceItemTypeEnum::ParticipantRegistration, null, $participant),
                    'issue_date' => Carbon::now(),
                    'due_date' => Carbon::now(),
                    'price' => $request->fee_type == FeeTypeEnum::International->value
                        ? $participant->eventEventCategory->international_fee
                        : ($request->fee_type == FeeTypeEnum::Local->value
                            ? $participant->eventEventCategory->local_fee
                            : null
                        ),
                    'status' => InvoiceStatusEnum::Paid,
                    'state' => InvoiceStateEnum::Complete,
                    'send_on' => Carbon::now(),
                    // 'charge_id' => $request->charge_id ?? null
                ]);


                // TODO: @ulrich - Create an activity for this. It is important to know who created the invoice. Also, in places where operations (like the invoice) get created by the system, we should have a way to have it identified properly

                $participant->invoiceItem()->create([
                    'invoice_id' => $invoice->id,
                    'type' => InvoiceItemTypeEnum::ParticipantRegistration,
                    'status' => InvoiceItemStatusEnum::Paid,
                    'price' => $invoice->price
                ]);

                $message = ' (a paid invoice was generated and attached to them)';
            }

            $participant->save();

            return (object) [
                'status' => true,
                'message' => "Set participant payment status successful!",
                'participant' => $participant,
                '_message' => $message ?? null
            ];
        }
    }

    /**
     * Update participant status
     *
     * @param  mixed $participant
     * @return object
     */
    public static function updateParticipantStatus(Participant $participant): object
    {
        $regResult = static::validateRegFields($participant);
        $customFieldsResult = static::validateCustomFields($participant);

        if ($regResult['status'] && $customFieldsResult['status'] && $participant->status != ParticipantStatusEnum::Transferred) {
            $participant->update(['status' => ParticipantStatusEnum::Complete]);
        } else {
            $participant->update(['status' => ParticipantStatusEnum::Incomplete]);
        }

        return (object) [
            ...$regResult,
            'status' => $regResult['status'] * $customFieldsResult['status'],
            'errors' => [
                ...$regResult['errors'],
                'custom_fields' => [...$customFieldsResult['errors']]
            ]
        ];
    }

    /**
     * Check if the participant can complete registration (if there are available places)
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function canCompleteRegistration()
    {
        if ($this->isConsideredAmongCompletedParticipants()) {
            return true;
        } else { // Check if there are available places
            $hasAvailablePlaces = $this->eventEventCategory->_hasAvailablePlaces(null, $this->charity); // Check if there are available places for registration

            if (! $hasAvailablePlaces->status) {
                throw new Exception($hasAvailablePlaces->message);
            }

            $this->status = ParticipantStatusEnum::Incomplete;
            $this->save();

            // TODO: Prompt the user to make payment since there are still available places
            throw new Exception("Registration Incomplete. Payment is expected!");
        }
    }

    /**
     * Check if a user exists by their email address.
     *
     * This function searches the database for a user with the specified email address.
     * If the user exists, it returns an object containing the status, a success message, 
     * the user data, and a flag indicating the user was not recently created.
     * If the user does not exist, it returns null.
     *
     * @param string $email The email address to search for.
     * @return object|null An object with user information if the user exists, or null if not.
     */
    public static function CheckByEmail($email)
    {
        // Check if the user exists
        $user = User::where('email', $email)->first();

        // Return user data if exists, otherwise return null
        if ($user) {

            return (object) [
                'status' => true,
                'message' => "User already exist!",
                'user' => $user,
                'wasRecentlyCreated' => false
            ];
        }
        return null;
    }

}
