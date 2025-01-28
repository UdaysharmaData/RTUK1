<?php

namespace App\Modules\Setting\Enums;

use ArchTech\Enums\Names;
use App\Traits\SiteTrait;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\Exceptions;
use App\Modules\Setting\Enums\EnvironmentEnum;

enum SiteEnum: string implements Exceptions
{
    use Options, Names, _Options, SiteTrait;

    case SportForCharity = 'sportforcharity.com';

    case RunForCharity = 'runforcharity.com';
    
    case CycleForCharity = 'cycleforcharity.com';
 
    case XeroForCharity = 'xeroforcharity.com';

    case RunThroughHub = 'runthroughhub.com';

    case RunThrough = 'www.runthrough.co.uk';

    case VirtualMarathonSeries = 'virtualmarathonseries.com';

    case SportsMediaAgency = 'sportsmediaagency.com';

    case RunningGrandPrix = 'runninggrandprix.com';

    case Leicestershire10K = 'leicestershire10k.com';

    /**
     * Use their defined label instead of the RegexHelper
     * // TODO: Get a better name for this method and its interface.
     * 
     * @return array
     */
    public static function exceptions(): array 
    {
        return [
            'RunThroughHub' => 'RunThroughHub',
            'RunThrough' => 'RunThrough',
        ];
    }

    /**
     * Get the general platform
     * 
     * @return \App\Modules\Setting\Enums\SiteEnum
     */
    public static function generalSite(): \App\Modules\Setting\Enums\SiteEnum
    {
        return env('APP_ENV') == 'production'
            ? SiteEnum::SportsMediaAgency
            : SiteEnum::SportsMediaAgency;
    }

    /**
     * @param  \App\Modules\Setting\Enums\SiteEnum  $site
     * @return array
     */
    public static function env(\App\Modules\Setting\Enums\SiteEnum $site): array 
    {
        $var = [
            'runforcharity.com' => [
                EnvironmentEnum::Website->value => [
                    'local' => 'dev2.sportsmediaagency.com',
                    'development' => 'dev2.sportsmediaagency.com',
                    'staging' => 'staging6.sportsmediaagency.com',
                    'production' => 'runforcharity.com'    
                    ],
                EnvironmentEnum::Portal->value => [
                    'local' => 'dev3.sportsmediaagency.com',
                    'development' => 'dev3.sportsmediaagency.com',
                    'staging' => 'staging7.sportsmediaagency.com',
                    'production' => 'portal.runforcharity.com'
                ]
            ],
            'www.runthrough.co.uk' => [
                EnvironmentEnum::Website->value => [
                    'local' => 'dev2.sportsmediaagency.com',
                    'development' => 'dev2.sportsmediaagency.com',
                    'staging' => 'staging2.sportsmediaagency.com',
                    'production' => 'www.runthrough.co.uk'
                    ],
                EnvironmentEnum::Portal->value => [
                    'local' => 'dev2.sportsmediaagency.com',
                    'development' => 'dev2.sportsmediaagency.com',
                    'staging' => 'staging3.sportsmediaagency.com',
                    'production' => 'portal.runthrough.co.uk'
                ]
            ],
            'runninggrandprix.com' => [
                EnvironmentEnum::Website->value => [
                    'local' => 'dev2.sportsmediaagency.com',
                    'development' => 'dev2.sportsmediaagency.com',
                    'staging' => 'staging4.sportsmediaagency.com',
                    'production' => 'runninggrandprix.com'
                ],
                EnvironmentEnum::Portal->value => [
                    'local' => 'dev2.sportsmediaagency.com',
                    'development' => 'dev2.sportsmediaagency.com',
                    'staging' => 'staging5.sportsmediaagency.com',
                    'production' => 'portal.runninggrandprix.com'
                ]
            ],
            'leicestershire10k.com' => [
                EnvironmentEnum::Website->value => [
                    'local' => 'dev2.sportsmediaagency.com',
                    'development' => 'dev2.sportsmediaagency.com',
                    'staging' => 'staging6.sportsmediaagency.com',
                    'production' => 'leicestershire10k.com'
                ],
                EnvironmentEnum::Portal->value => [
                    'local' => 'dev2.sportsmediaagency.com',
                    'development' => 'dev2.sportsmediaagency.com',
                    'staging' => 'staging7.sportsmediaagency.com',
                    'production' => 'portal.leicestershire10k.com'
                ]
            ]
        ];

        return $var[$site->value] ?? [];
    }

    /**
     * Get site domain names for each environment
     * 
     * @param  \App\Modules\Setting\Enums\SiteEnum|null  $site
     * @param  EnvironmentEnum           $_env. Either of website or portal
     * @return string|null
     */
    public static function environmentWebsite(?\App\Modules\Setting\Enums\SiteEnum $site = null, EnvironmentEnum $_env = EnvironmentEnum::Website): ?string
    {
        if (!$site) return null;

        $domain = static::env($site);
        $env = config('app.env');

        return $domain[$_env->value][$env] ?? null;
    }

    /**
     * @param  \App\Modules\Setting\Enums\OrganisationEnum  $organisation
     * @return array
     */
    public static function organisationSites(\App\Modules\Setting\Enums\OrganisationEnum $organisation): array 
    {
        $var = [
            \App\Modules\Setting\Enums\OrganisationEnum::GWActive->value => [
                \App\Modules\Setting\Enums\SiteEnum::RunThrough->value,
                \App\Modules\Setting\Enums\SiteEnum::RunningGrandPrix->value,
                \App\Modules\Setting\Enums\SiteEnum::Leicestershire10K->value
            ],
            \App\Modules\Setting\Enums\OrganisationEnum::SportsMediaAgency->value => [
                \App\Modules\Setting\Enums\SiteEnum::RunForCharity->value,
                \App\Modules\Setting\Enums\SiteEnum::SportForCharity->value,
                \App\Modules\Setting\Enums\SiteEnum::CycleForCharity->value,
                \App\Modules\Setting\Enums\SiteEnum::XeroForCharity->value,
                \App\Modules\Setting\Enums\SiteEnum::VirtualMarathonSeries->value,
                \App\Modules\Setting\Enums\SiteEnum::SportsMediaAgency->value
            ]
        ];

        return $var[$organisation->value] ?? [];
    }

    /**
     * Check if a given site belongs to the organisation
     * 
     * @param  \App\Modules\Setting\Enums\OrganisationEnum            $organisation
     * @param  \App\Modules\Setting\Models\Site|null  $site
     * @return bool
     */
    public static function belongsToOrganisation(\App\Modules\Setting\Enums\OrganisationEnum $organisation, \App\Modules\Setting\Models\Site|null $site = null): bool
    {
        return in_array($site?->domain ?? static::getSite()?->domain, static::organisationSites($organisation));
    }

    /**
     * @param  \App\Modules\Setting\Enums\OrganisationEnum  $organisation
     * @return SiteEnum
     */
    public static function mainSiteInOrganisation(\App\Modules\Setting\Enums\OrganisationEnum $organisation): SiteEnum 
    {
        $var = [
            \App\Modules\Setting\Enums\OrganisationEnum::GWActive->value => \App\Modules\Setting\Enums\SiteEnum::RunThrough,
            \App\Modules\Setting\Enums\OrganisationEnum::SportsMediaAgency->value => \App\Modules\Setting\Enums\SiteEnum::RunForCharity
        ];

        return $var[$organisation->value] ?? null;
    }

    /**
     * @param  \App\Modules\Setting\Enums\OrganisationEnum  $organisation
     * @param  \App\Modules\Setting\Enums\SiteEnum          $site
     * @return bool
     */
    public static function isMainSiteInOrganization(\App\Modules\Setting\Enums\OrganisationEnum $organisation, SiteEnum $site): bool 
    {
        return static::mainSiteInOrganisation($organisation) == $site;

    }
    
}