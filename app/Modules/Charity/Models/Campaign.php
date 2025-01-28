<?php

namespace App\Modules\Charity\Models;

use App\Enums\CampaignStatusEnum;
use App\Enums\CampaignPackageEnum;
use App\Modules\Event\Models\Event;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Enums\CampaignLeadChannelEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Campaign extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'campaigns';

    protected $fillable = [
        'charity_id',
        'user_id', // account_manager? event_manager?
        'title',
        'package',
        'status',
        'start_date',
        'end_date',
        'notification_trigger'
    ];

    protected $casts = [
        'package' => CampaignPackageEnum::class,
        'status' => CampaignStatusEnum::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    /**
     * Get the charity that owns the campaign.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the user(account_manager) that owns the campaign.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * The events that belong to the campaign.
     * @return BelongsToMany
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->using(CampaignEvent::class)->withPivot('id', 'created_at', 'updated_at');
    }

    /**
     * The campaign leads the belong to the campaign
     * @return HasMany
     */
    public function campaignLeads(): HasMany
    {
        return $this->hasMany(CampaignLead::class);
    }

    /**
     * Create campaign lead channels
     * TODO: Revise this implementation while working on the CampaignController
     * 
     * @param  int|null $leadsNotificationTrigger
     * @return void
     */
    public function wizard(?int $leadsNotificationTrigger = null): void
    {
        foreach (CampaignLeadChannelEnum::cases() as $channel) {
            $lead = CampaignLead::firstOrNew([
                'campaign_id' => $this->id,
                'channel' => $channel->value
            ]);

            $lead->setThreshold($this);
            $lead->notification_trigger = $leadsNotificationTrigger ?? $lead->notification_trigger;
            $lead->save();
        }
    }


}
