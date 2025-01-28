<?php

namespace App\Modules\User\Traits;

use App\Models\ConnectedDevice;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasConnectedDevices
{
    /**
     * @return HasMany
     */
    public function connectedDevices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConnectedDevice::class);
    }
}
