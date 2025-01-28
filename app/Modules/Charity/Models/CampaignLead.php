<?php

namespace App\Modules\Charity\Models;

use App\Enums\CampaignPackageEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Enums\CampaignLeadChannelEnum;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Charity\Models\Campaign;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampaignLead extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'campaign_leads';

    protected $fillable = [
        'campaign_id',
        'channel',
        'count',
        'threshold',
        'notification_trigger',
    ];

    protected $casts = [
        'channel' => CampaignLeadChannelEnum::class
    ];

    /**
     * The campaign the owns the campaign lead
     * @return BelongsTo
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Set the threshold
     * 
     * @return void
     */
    public function setThreshold(Campaign $campaign)
    {
        switch ($campaign->package) {
            case CampaignPackageEnum::Leads_25:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisRFC:
                        $this->threshold = 25;
                        break;

                    default:
                        $this->threshold = 0;
                        break;
                }
                break;
            case CampaignPackageEnum::Leads_50:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisRFC:
                        $this->threshold = 50;
                        break;

                    default:
                        $this->threshold = 0;
                        break;
                }
                break;
            case CampaignPackageEnum::Leads_100:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisFlagShip:
                        $this->threshold = 30;
                        break;

                    default:
                        $this->threshold = null;
                        break;
                }
                break;

            case CampaignPackageEnum::Leads_250:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisFlagShip:
                        $this->threshold = 75;
                        break;

                    default:
                        $this->threshold = null;
                        break;
                }
                break;

            case CampaignPackageEnum::Leads_500:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisFlagShip:
                        $this->threshold = 150;
                        break;

                    default:
                        $this->threshold = null;
                        break;
                }
                break;

            case CampaignPackageEnum::Leads_1000:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisOwnPlace:
                        $this->threshold = 100;
                        break;

                    case CampaignLeadChannelEnum::LetsDoThisFlagShip:
                        $this->threshold = 300;
                        break;

                    default:
                        $this->threshold = null;
                        break;
                }
                break;

            case CampaignPackageEnum::Leads_2500:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisOwnPlace:
                        $this->threshold = 250;
                        break;

                    case CampaignLeadChannelEnum::LetsDoThisFlagShip:
                        $this->threshold = 750;
                        break;

                    default:
                        $this->threshold = null;
                        break;
                }
                break;

            case CampaignPackageEnum::Leads_5000:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisOwnPlace:
                        $this->threshold = 500;
                        break;

                    case CampaignLeadChannelEnum::LetsDoThisFlagShip:
                        $this->threshold = 1500;
                        break;

                    default:
                        $this->threshold = null;
                        break;
                }
                break;

            case CampaignPackageEnum::Classic:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisRFC:
                        $this->threshold = 25;
                        break;

                    default:
                        $this->threshold = 0;
                        break;
                }
                break;

            case CampaignPackageEnum::Premium:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisRFC:
                        $this->threshold = 50;
                        break;

                    default:
                        $this->threshold = 0;
                        break;
                }
                break;

            case CampaignPackageEnum::Year_2:
                switch ($this->channel) {
                    case CampaignLeadChannelEnum::LetsDoThisRFC:
                        $this->threshold = 100;
                        break;

                    default:
                        $this->threshold = 0;
                        break;
                }
                break;

            default:
                switch ($this->channel) {
                    default:
                        $this->threshold = null;
                        break;
                }
                break;
        }
    }
}
