<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\FundraisingEmailTemplateEnum;
use App\Enums\FundraisingEmailScheduleTypeEnum;
use App\Modules\Charity\Models\CharityFundraisingEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Replaces the Drips Model
 */
class FundraisingEmail extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'fundraising_emails';

    protected $fillable = [
        'status',
        'name',
        'subject',
        'schedule_type',
        'schedule_days',
        'template'
    ];

    protected $casts = [
        'status' => 'boolean',
        'schedule_type' => FundraisingEmailScheduleTypeEnum::class,
        'template' => FundraisingEmailTemplateEnum::class
    ];

    /**
     * The charities that have subscibed to the fundraising email.
     * @return BelongsToMany
     */
    public function charities(): BelongsToMany
    {
        return $this->belongsToMany(Charity::class)->using(CharityFundraisingEmail::class)->withPivot('id', 'status', 'content', 'from_name', 'from_email')->withTimestamps();
    }
}
