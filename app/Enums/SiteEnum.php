<?php

namespace App\Enums;

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
     * @return \App\Enums\SiteEnum
     */
    public static function generalSite(): \App\Enums\SiteEnum
    {
        return env('APP_ENV') == 'production'
            ? SiteEnum::SportsMediaAgency
            : SiteEnum::SportsMediaAgency;
    }

    /**
     * @param  \App\Enums\SiteEnum  $site
     * @return array
     */
    public static function env(\App\Enums\SiteEnum $site): array
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
     * @param  \App\Enums\SiteEnum|null  $site
     * @param  EnvironmentEnum           $_env. Either of website or portal
     * @return string|null
     */
    public static function environmentWebsite(?\App\Enums\SiteEnum $site = null, EnvironmentEnum $_env = EnvironmentEnum::Website): ?string
    {
        if (!$site) return null;

        $domain = static::env($site);
        $env = config('app.env');

        return $domain[$_env->value][$env] ?? null;
    }

    /**
     * @param  \App\Enums\OrganisationEnum  $organisation
     * @return array
     */
    public static function organisationSites(\App\Enums\OrganisationEnum $organisation): array
    {
        $var = [
            \App\Enums\OrganisationEnum::GWActive->value => [
                \App\Enums\SiteEnum::RunThrough->value,
                \App\Enums\SiteEnum::RunningGrandPrix->value,
                \App\Enums\SiteEnum::Leicestershire10K->value
            ],
            \App\Enums\OrganisationEnum::SportsMediaAgency->value => [
                \App\Enums\SiteEnum::RunForCharity->value,
                \App\Enums\SiteEnum::SportForCharity->value,
                \App\Enums\SiteEnum::CycleForCharity->value,
                \App\Enums\SiteEnum::XeroForCharity->value,
                \App\Enums\SiteEnum::VirtualMarathonSeries->value,
                \App\Enums\SiteEnum::SportsMediaAgency->value
            ]
        ];

        return $var[$organisation->value] ?? [];
    }

    /**
     * Check if a given site belongs to the organisation
     *
     * @param  \App\Enums\OrganisationEnum            $organisation
     * @param  \App\Modules\Setting\Models\Site|null  $site
     * @return bool
     */
    public static function belongsToOrganisation(\App\Enums\OrganisationEnum $organisation, \App\Modules\Setting\Models\Site|null $site = null): bool
    {
        return in_array($site?->domain ?? static::getSite()?->domain, static::organisationSites($organisation));
    }

    /**
     * @param  \App\Enums\OrganisationEnum  $organisation
     * @return SiteEnum
     */
    public static function mainSiteInOrganisation(\App\Enums\OrganisationEnum $organisation): SiteEnum
    {
        $var = [
            \App\Enums\OrganisationEnum::GWActive->value => \App\Enums\SiteEnum::RunThrough,
            \App\Enums\OrganisationEnum::SportsMediaAgency->value => \App\Enums\SiteEnum::RunForCharity
        ];

        return $var[$organisation->value];
    }

    /**
     * @param  \App\Enums\OrganisationEnum  $organisation
     * @param  \App\Enums\SiteEnum          $site
     * @return bool
     */
    public static function isMainSiteInOrganization(\App\Enums\OrganisationEnum $organisation, SiteEnum $site): bool
    {
        return static::mainSiteInOrganisation($organisation) == $site;

    }

}
