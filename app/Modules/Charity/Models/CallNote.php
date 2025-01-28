<?php

namespace App\Modules\Charity\Models;

use App\Enums\CallNoteCallEnum;
use App\Enums\CallNoteStatusEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CharityMembershipTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallNote extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'call_notes';

    protected $fillable = [
        'charity_id',
        'year',
        'call',
        'note',
        'status'
    ];

    protected $casts = [
        'call' => CallNoteCallEnum::class,
        'status' => CallNoteStatusEnum::class
    ];

    public static $callOptions = [
        '23_months' => [
            'title' => '23 Months Calls',
            'monthsDue' => 1,
            'membershipType' => [CharityMembershipTypeEnum::TwoYear]
        ],
        '21_months' => [
            'title' => '21 Months Calls',
            'monthsDue' => 3,
            'membershipType' => [CharityMembershipTypeEnum::TwoYear]
        ],
        '18_months' => [
            'title' => '18 Months Calls',
            'monthsDue' => 6,
            'membershipType' => [CharityMembershipTypeEnum::TwoYear]
        ],
        '15_months' => [
            'title' => '15 Months Calls',
            'monthsDue' => 9,
            'membershipType' => [CharityMembershipTypeEnum::TwoYear]
        ],
        '12_months' => [
            'title' => '12 Months Calls',
            'monthsDue' => 12,
            'membershipType' => [CharityMembershipTypeEnum::TwoYear]
        ],
        '11_months' => [
            'title' => '11 Months Calls',
            'monthsDue' => 1,
            'membershipType' => [CharityMembershipTypeEnum::Classic, CharityMembershipTypeEnum::Premium]
        ],
        '8_months' => [
            'title' => '8 Months Calls',
            'monthsDue' => 4,
            'membershipType' => [CharityMembershipTypeEnum::Classic, CharityMembershipTypeEnum::Premium]
        ],
        '5_months' => [
            'title' => '5 Months Calls',
            'monthsDue' => 7,
            'membershipType' => []
        ],
        '2_months' => [
            'title' => '2 Months Calls',
            'monthsDue' => 10,
            'membershipType' => []
        ],
        '1_month' => [
            'title' => '1 Month Calls',
            'monthsDue' => [11, 23],
            'membershipType' => [[CharityMembershipTypeEnum::Classic, CharityMembershipTypeEnum::Premium], [CharityMembershipTypeEnum::TwoYear]]
        ],
        // 'all' => [
        //     'title' => 'All Calls This Month',
        //     'charities' => []
        // ]
    ];

    /**
     * Get the charity that owns the call note.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }
}
