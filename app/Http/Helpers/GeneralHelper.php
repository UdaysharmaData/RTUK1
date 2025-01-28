<?php

use App\Enums\SettingCustomFieldKeyEnum;
use App\Modules\Finance\Enums\AccountTypeEnum;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Wallet;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

if (!function_exists('getSettingCustomField')) {
    function getSettingCustomField(SettingCustomFieldKeyEnum $key)
    {
        if (app()->runningInConsole()) {
            $site = siteSetting();
        } else {
            $site = clientSite();
        }

        $setting = $site->setting()->firstOrCreate();

        try {
            $settingCustomFied = $setting->settingCustomFields()->where('key', $key->value)->firstOrFail();

            return $settingCustomFied;
        } catch (ModelNotFoundException $e) {
            Log::channel($site->code . 'adminanddeveloper')->debug("Custom Field Exception. The key {$key->value} was not found.");

            throw new \Exception("An error occurred. Please try again in a while.");
        }
    }
}

if (!function_exists('getSettingCustomFieldValue')) {
    function getSettingCustomFieldValue(SettingCustomFieldKeyEnum $key)
    {
        return getSettingCustomField($key)->value;
    }
}

if (!function_exists('generateAccountName')) {
    function generateAccountName(Wallet $wallet, Account $account, AccountTypeEnum $accountTypeEnum): string
    {
        $walletId = $wallet->id;
        $accountId = $account->id;
        $accounType = $accountTypeEnum->name;

        return "#$walletId:$accountId:$accounType";
    }
}
