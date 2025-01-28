<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Models\ConnectedDevice;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\DisconnectDevicesRequest;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DisconnectDevices extends Controller
{
    use Response;

    /**
     * Remove Devices
     *
     * Disconnect multiple devices from accessing user account.
     *
     * @group Authentication
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     * @bodyParam devices[] string required The array of valid ids belonging to devices to be disconnected. Example: [1, 2, 3]
     *
     * @param DisconnectDevicesRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function __invoke(DisconnectDevicesRequest $request, User $user): JsonResponse
    {
        try {
            ConnectedDevice::find($request->validated())
                ->each(function($device) {
                    $device->tokens()->delete();
                    $device->delete();
            });

            return $this->success('Device(s) Disconnected.', 200, [
                'connected_devices' => $user->fresh()
                    ->connectedDevices()
                    ->whereNotNull('device')
                    ->get()
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops...We couldn't find requesting user's info.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to disconnect a device.', 400);
        }
    }
}
