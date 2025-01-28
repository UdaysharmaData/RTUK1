<?php

namespace App\Traits\Enum;

use Auth;
use BackedEnum;
use App\Traits\SiteTrait;
use App\Http\Helpers\RegexHelper;
use Illuminate\Support\Collection;
use App\Modules\Setting\Enums\OrganisationCodeEnum;

trait _Options
{
    use SiteTrait;

    /** 
     * Get an associative array of [case value => case name].
     * 
     * @param  array       $excludes  An array of constants not to return. The items in the array should be an instance of BackedEnum
     * @return Collection
     * 
     * */
    public static function _options(array $excludes = []): Collection
    {
        $cases = static::cases();

        if (isset($cases[0]) && $cases[0] instanceof BackedEnum) {
            $cases = array_column($cases, 'value', 'name');
        } else {
            $cases = array_column($cases, 'value');
        }

        if (count($excludes)) { // Don't return the set constants
            foreach ($excludes as $exclude) {
                if ($exclude instanceof BackedEnum) {
                    if ($_exclude = get_class()::tryFrom($exclude->value)) {
                        unset($cases[$_exclude->name]);
                    }
                }
            }
        }

        if (method_exists(get_class(), 'roleExcludes')) { // An array of constants not to return for each of the specified roles
            foreach (static::roleExcludes() as $key => $exceptions) {
                if (Auth::check() && Auth::user()->activeRole?->role?->name->name == $key) { // If an array of constants was set for a given role, don't return the set constants
                    foreach ($exceptions as $exception) {
                        if ($exception instanceof BackedEnum) {
                            if ($_exception = get_class()::tryFrom($exception->value)) {
                                unset($cases[$_exception->name]);
                            }
                        }
                    }
                }
            }
        }

        if (method_exists(get_class(), 'siteExcludes')) { // An array of constants not to return for each of the specified sites
            foreach (static::siteExcludes() as $key => $exceptions) {
                if (static::getSite()?->code == $key) { // If an array of constants was set for a given site, don't return the set constants
                    foreach ($exceptions as $exception) {
                        if ($exception instanceof BackedEnum) {
                            if ($_exception = get_class()::tryFrom($exception->value)) {
                                unset($cases[$_exception->name]);
                            }
                        }
                    }
                }
            }
        }

        if (method_exists(get_class(), 'organisationExcludes')) { // An array of constants not to return for each of the specified sites belonging to the given organisation
            foreach (static::organisationExcludes() as $key => $exceptions) {
                if (in_array(static::getSite()?->organisation?->code, array_column(OrganisationCodeEnum::cases(), 'value'))) { // If an array of constants was set for a given organisation, don't return the set constants
                    foreach ($exceptions as $exception) {
                        if ($exception instanceof BackedEnum) {
                            if ($_exception = get_class()::tryFrom($exception->value)) {
                                unset($cases[$_exception->name]);
                            }
                        }
                    }
                }
            }
        }

        return static::toLableValuePair($cases);
    }

    /**
     * Convert 
     * 
     * @param  Array      $array
     * @return Collection
     */
    private static function toLableValuePair(array $array): Collection
    {
        return collect($array)->map(function ($value, $key) {
            if (method_exists(get_class(), 'exceptions') && in_array($key, array_keys(static::exceptions()))) {
                $label = static::exceptions()[$key];
            } else {
                $label = RegexHelper::format($key);
            }

            return [
                'label' => $label,
                'value' => $value
            ];
        })->values();
    }

    /**
     * Format the name 
     * 
     * @return string
     */
    public function formattedName(): string
    {
        return RegexHelper::format($this->name);
    }
}