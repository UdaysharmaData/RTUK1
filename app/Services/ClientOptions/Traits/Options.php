<?php

namespace App\Services\ClientOptions\Traits;

use App\Enums\RedirectHardDeleteStatusEnum;
use App\Enums\RedirectsListOrderByFieldsEnum;
use App\Enums\RedirectSoftDeleteStatusEnum;
use App\Enums\RedirectStatusEnum;
use App\Enums\RedirectTypeEnum;
use App\Enums\RolesListOrderByFieldsEnum;
use App\Enums\SiteUserActionEnum;
use App\Enums\SiteUserStatus;
use App\Enums\UserVerificationStatus;
use App\Filters\RedirectOrderByFilter;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\RoleDataService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

use App\Enums\MonthEnum;
use App\Enums\GenderEnum;
use App\Enums\PageStatus;
use App\Enums\BoolYesNoEnum;
use App\Enums\ExperiencesListOrderByFieldsEnum;
use App\Enums\TimeReferenceEnum;
use App\Enums\SocialPlatformEnum;
use App\Enums\OrderByDirectionEnum;
use App\Enums\PagesListOrderByFieldsEnum;
use App\Enums\UsersListOrderByFieldsEnum;
use App\Enums\ListingFaqsFilterOptionsEnum;
use App\Enums\DefaultListOrderByFieldsEnum;
use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Enums\CombinationsListOrderByFieldsEnum;
use App\Enums\ListDraftedItemsOptionsEnum;
use App\Enums\ListingMedalsFilterOptionsEnum;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Enums\UploadTypeEnum;
use App\Services\Analytics\Enums\InteractionTypeEnum;

use App\Models\Page;
use App\Models\Invoice;
use App\Models\Combination;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Participant\Models\Participant;

trait Options
{
    /**
     * @return mixed
     */
    private function getRoleOptions(): mixed
    {
        $key = 'roles-site:'.clientSiteId();
            request()->user()?->isGeneralAdmin() && $key.= '+access-type=general';

        return Cache::remember(
            $key,
            now()->addHour(), fn() => Role::query()
                ->select(['id', 'name', 'description'])
                ->get()
        );
    }

    /**
     * @return mixed
     */
    private function getTshirtOptions(): mixed
    {
        return Cache::remember('tshirt-sizes', now()->addHour(), function () {
            return collect(ParticipantProfileTshirtSizeEnum::options())->map(function ($size, $key) {
                return ['label' => $key, 'value' => $size];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    private function getGenderOptions(): mixed
    {
        return Cache::remember('genders', now()->addHour(), function () {
            return collect(GenderEnum::options())->map(function ($gender, $key) {
                return ['label' => $key, 'value' => $gender];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    private function getSocialsOptions(): mixed
    {
        return Cache::remember('socials', now()->addHour(), function () {
            return collect(SocialPlatformEnum::options())->map(function ($platform, $key) {
                return ['label' => $key, 'value' => $platform];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    private function getUsersOrderByOptions(): mixed
    {
        return Cache::remember('users-list-order-by-filter-options', now()->addHour(), function () {
            return UsersListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getRolesOrderByOptions(): mixed
    {
        return Cache::remember('roles-list-order-by-filter-options', now()->addHour(), function () {
            return RolesListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getTimePeriodReferenceOptions(): mixed
    {
        return Cache::remember('time-period-reference-options', now()->addHour(), function () {
            return collect(TimeReferenceEnum::options())->map(function ($option, $key) {
                return [
                    'label' => $option,
                    'value' => $option
                ];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    private function getPagesStatusOptions(): mixed
    {
        return PageStatus::_options();
    }

    /**
     * @return mixed
     */
    private function getPageStatusFilterOptions(): mixed
    {
        return Cache::remember('pages-status-filters', now()->addHour(), function () {
            return collect(PageStatus::options())->map(function ($option, $key) {
                return [
                    'label' => ucwords($key),
                    'value' => lcfirst($key)
                ];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    private function getInteractionTypes(): mixed
    {
        return Cache::remember('analytics-interaction-types-filters', now()->addHour(), function () {
            return InteractionTypeEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getPagesOrderByOptions(): mixed
    {
        return Cache::remember('pages-order-by-filter-options', now()->addHour(), function () {
            return PagesListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getCombinationsOrderByOptions(): mixed
    {
        return Cache::remember('combinations-order-by-filter-options', now()->addHour(), function () {
            return CombinationsListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getOrderByDirectionOptions(): mixed
    {
        return Cache::remember('order-by-direction-filter-options', now()->addHour(), function () {
            return OrderByDirectionEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getUserSiteStatusOptions(): mixed
    {
        return Cache::remember('get-user-site-status-options', now()->addHour(), function () {
            return SiteUserStatus::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getUserVerificationStatusOptions(): mixed
    {
        return Cache::remember('get-user-verification-status-options', now()->addHour(), function () {
            return UserVerificationStatus::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getUserSiteActionOptions(): mixed
    {
        return Cache::remember('get-user-site-action-options', now()->addHour(), function () {
            return SiteUserActionEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getStatsYearFilterOptions(): mixed
    {
        return Cache::remember('user_stats_year_filter_options', now()->addMonth(), function () {
            $years = User::query()
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->orderByDesc('year')
                ->pluck('year');

            return $years->map(function ($option, $key) {
                return [
                    'label' => $option,
                    'value' => $option
                ];
            });
        });
    }

    /**
     * @return mixed
     */
    private function getPagesYearFilterOptions(): mixed
    {
        return Cache::remember('get_pages_year_filter_options', now()->addMonth(), function () {
            $years = Page::query()
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->orderByDesc('year')
                ->pluck('year');

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => (string) $option
                ];
            });
        });
    }

    /**
     * @return mixed
     */
    private function getCombinationsYearFilterOptions(): mixed
    {
        return Cache::remember('get_combinations_year_filter_options', now()->addMonth(), function () {
            $years = Combination::query()
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->orderByDesc('year')
                ->pluck('year');

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => (string) $option
                ];
            });
        });
    }

    /**
     * @return mixed
     */
    private function getPagesWithDeletedOptions(): mixed
    {
        return Cache::remember('pages-with-deleted', now()->addHour(), function () {
            $options = ['yes', 'no'];
            return collect($options)->map(function ($option, $key) {
                return [
                    'label' => ucwords($option),
                    'value' => $option
                ];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    private function getPagesDeletedOnlyOptions(): mixed
    {
        return Cache::remember('pages-deleted-only', now()->addHour(), function () {
            $options = ['yes', 'no'];
            return collect($options)->map(function ($option, $key) {
                return [
                    'label' => ucwords($option),
                    'value' => $option
                ];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    private function getListWithFaqsOptions(): mixed
    {
        return Cache::remember('list-with-or-without-faqs-options', now()->addHour(), function () {
            return ListingFaqsFilterOptionsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    private function getListWithMedalsOptions(): mixed
    {
        return Cache::remember('list-with-or-without-medals-options', now()->addHour(), function () {
            return ListingMedalsFilterOptionsEnum::_options();
        });
    }

    /**
     * @return Collection
     */
    private function getListDeletedFilterOptions(): \Illuminate\Support\Collection
    {
        return Cache::remember('List-deleted-filter-options', now()->addHour(), function () {
            return ListSoftDeletedItemsOptionsEnum::_options();
        });
    }

    private function getListDraftedFilterOptions()
    {
        return Cache::remember('List-drafted-filter-options', now()->addHour(), function () {
            return ListDraftedItemsOptionsEnum::_options();
        });
    }

    private function getMonthFilterOptions()
    {
        return Cache::remember('get-months-filter-options', now()->addHour(), function () {
            return MonthEnum::_options();
        });
    }

    private function getYesNoOptions()
    {
        return Cache::remember('get-yes-no-list-filter-options', now()->addHour(), function () {
            return BoolYesNoEnum::_options();
        });
    }
    
    /**
     * getUploadsTypeOptions
     *
     * @return mixed
     */
    private function getUploadsTypeOptions(): mixed
    {
        return Cache::remember('get-uploads-type-options', now()->addHour(), function() {
            return UploadTypeEnum::_options([UploadTypeEnum::CSV, UploadTypeEnum::PDF]);
        });
    }

    /**
     *
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('order-by-filter-options', now()->addHour(), function () {
            return DefaultListOrderByFieldsEnum::_options();
        });
    }

    /**
     *
     * @return mixed
     */
    public static function getRedirectsTypeOptions(): mixed
    {
        return Cache::remember('get-redirects-type-options', now()->addHour(), function () {
            return RedirectTypeEnum::_options();
        });
    }

    /**
     *
     * @return mixed
     */
    public static function getRedirectSoftDeleteStatusOptions(): mixed
    {
        return Cache::remember('get-redirects-soft-delete-status-options', now()->addHour(), function () {
            return RedirectSoftDeleteStatusEnum::_options();
        });
    }

    /**
     *
     * @return mixed
     */
    public static function getRedirectHardDeleteStatusOptions(): mixed
    {
        return Cache::remember('get-redirects-hard-delete-status-options', now()->addHour(), function () {
            return RedirectHardDeleteStatusEnum::_options();
        });
    }

    public static function getRedirectOrderByOptions(): mixed
    {
        return Cache::remember('get-redirects-order-by-options', now()->addHour(), function () {
            return RedirectsListOrderByFieldsEnum::_options();
        });
    }
}
