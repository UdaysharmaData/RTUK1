<?php

namespace App\Console\Commands;

use Log;
use Exception;
use Carbon\Carbon;
use App\Modules\Setting\Enums\SiteEnum;
use App\Traits\SiteTrait;
use Illuminate\Console\Command;
use App\Enums\EnquiryActionEnum;
use App\Traits\AdministratorEmails;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Enquiry\Models\Enquiry;

class PendingEnquiriesNotificationCommand extends Command
{
    use AdministratorEmails, SiteTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enquiry:notify-about-pending {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify admin(s) and charities (for rfc) about enquiries created due to attempted registrations to events having exhausted places.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $site = Site::whereName($value = $this->argument('site'))
                ->orWhere('domain', $value)
                ->orWhere('code', $value)
                ->firstOrFail();

            // Check if there are attempted registrations due to eec exhausted places made yesterday and notify the admin(s)

            $enquiries = Enquiry::whereNull('participant_id')
                ->whereDate('created_at', Carbon::yesterday())
                ->where('action', EnquiryActionEnum::RegistrationFailed_EventPlacesExhausted)
                ->whereHas('site', function ($query) use ($site) {
                    $query->where('id', $site->id);
                })->whereHas('event', function ($query) use ($site) {
                    $query->active(true)
                        ->whereHas('eventCategories', function ($query) use ($site) {
                            $query->whereHas('site', function ($query) use ($site) {
                                $query->where('id', $site->id);
                            });
                        });
                })->get();

            if ($enquiries->count()) { // Notify the admins about attempted registrations to events having exhausted places
                $link = static::getSite()?->url."/enquiries/website?action=".EnquiryActionEnum::RegistrationFailed_EventPlacesExhausted->value;
                // TODO: Email the admin about these enquiries
            }

            if ($site->domain == SiteEnum::RunForCharity->value) { // Only concerns Run For Charity
                Charity::whereNull('deleted_at')
                    ->whereHas('enquiries', function ($query) use ($site) { // Check if there are attempted registrations due to charity exhausted places made yesterday and notify the charities concerned
                        $query->whereNull('participant_id')
                            ->whereDate('created_at', Carbon::yesterday())
                            ->where('action', EnquiryActionEnum::RegistrationFailed_CharityPlacesExhausted)
                            ->where('site_id', $site->id)
                            ->whereHas('event', function ($query) use ($site) {
                                $query->active(true)
                                    ->whereHas('eventCategories', function ($query) use ($site) {
                                        $query->whereHas('site', function ($query) use ($site) {
                                            $query->where('id', $site->id);
                                        });
                                    });
                            });
                        })->selectSub(function (\Illuminate\Database\Query\Builder $query) use ($site) {
                            $query->selectRaw('COUNT(*)')
                                ->from('enquiries')
                                ->whereColumn('charities.id', '=', 'enquiries.charity_id')
                                ->whereNull('enquiries.deleted_at')
                                ->whereDate('created_at', Carbon::yesterday())
                                ->where('action', EnquiryActionEnum::RegistrationFailed_CharityPlacesExhausted)
                                ->where('site_id', $site->id);
                        }, 'total')
                        ->get()->each(function ($charity) {
                            $link = static::getSite()?->url."/enquiries/website?action=".EnquiryActionEnum::RegistrationFailed_CharityPlacesExhausted->value;
                            // TODO: Email the charity about these enquiries
                        });
            }
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }
    
        return Command::SUCCESS;
    }
}
