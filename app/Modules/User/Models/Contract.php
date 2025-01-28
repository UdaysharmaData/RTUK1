<?php

namespace App\Modules\User\Models;

use App\Enums\ContractTypeEnum;
use App\Enums\ContractStateEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     *
     * one to many [contract - partner package] / check -> `AssignedPartnerPackage` model
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'title',
        'type', // membership_agreement, gdpr_agreement, partner_agreement, vms_agreement, other
        'state', // current, archived
        'agreement'
    ];

    protected $casts = [
        'type' => ContractTypeEnum::class,
        'state' => ContractStateEnum::class
    ];

    /**
     * @var string[]
     */
    public static $types = [
        'membership_agreement' => 'Membership Agreement',
        'gdpr_agreement' => 'GDPR Agreement',
        'partner_agreement' => 'Partner Agreement',
        'vms_agreement' => 'Virtual Marathon Series Agreement',
        'other_agreement' => 'Other Agreement'
    ];

    /**
     * @var string[]
     */
    public static $states = [
        'current' => 'Current',
        'archived' => 'Archived'
    ];

    /**
     * @var string[]
     */
    public static $rules = [
        'user_id' => 'required|integer|exists:users,id',
        'title' => 'required|max:255',
        'type' => 'required|in:membership_agreement,gdpr_agreement,partner_agreement,vms_agreement,other_agreement',
        'state' => 'required|in:current,archived',
        'agreement' => 'required|mimes:jpeg,png,pdf,docx,doc'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo // Most like account manager
    {
        return $this->belongsTo(User::class);
    }
}
