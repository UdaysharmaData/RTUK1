<?php

namespace App\Services\TwoFactorAuth\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

trait HandleSafeDevice
{
    /**
     * Return all the Safe Devices that bypass Two-Factor Authentication.
     *
     * @return Collection
     */
    public function safeDevices(): Collection
    {
        return $this->safe_devices ?? collect();
    }

    /**
     * Determines if the Request has been made through a previously used "safe" device.
     *
     * @return bool
     */
    public function isSafeDevice(): bool
    {
        $device = $this->getDeviceDetail();
        $device = $this->safeDevices()->where('device', $device['device'])
            ->where('country_code', $device['country_code'])
            ->first();

        if ($device) {
            $timestamp = Carbon::createFromTimestamp($device['added_at']);
            $isFuture = $timestamp->addDays(config('two-factor.safe_devices.expiration_days'))->isFuture();

            if (!$isFuture) {
                $this->safe_devices = $this->safeDevices()
                    ->where('device', '!=', $device['device'])
                    ->where('country_code', '!=',$device['country_code'])
                    ->all();
                $this->save();
            }
            return $isFuture;
        }

        return false;
    }

    /**
     * Adds a "safe" Device from the Request, and returns the token used.
     *
     */
    public function addSafeDevice(): void
    {
        $this->safe_devices = $this->safeDevices()
            ->push([
                ...$this->getDeviceDetail(),
                'added_at' => $this->freshTimestamp()->getTimestamp(),
            ])
            ->sortByDesc('added_at') // Ensure the last is the first, so we can slice it.
            ->slice(0, config('two-factor.safe_devices.max_devices', 3))
            ->values();

        $this->save();
    }

    private function getDeviceDetail(): array
    {
        $agent = new \Jenssegers\Agent\Agent();
        $device = $agent->device();
        $platform = $agent->platform();
        $browser = $agent->browser();
        $position = Location::get();

        return [
            'device' => $device ?: null,
            'platform' => $platform ?: null,
            'browser' => $browser ?: null,
            'ip' => request()->ip(),
            'country_code' => $position->countryCode,
            'country_name' => $position->countryName
        ];
    }
}
