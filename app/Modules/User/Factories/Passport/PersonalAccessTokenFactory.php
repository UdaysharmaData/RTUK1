<?php

namespace App\Modules\User\Factories\Passport;

use App\Models\ConnectedDevice;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\PersonalAccessTokenResult;

class PersonalAccessTokenFactory extends \Laravel\Passport\PersonalAccessTokenFactory
{
    /**
     * Create a new personal access token.
     *
     * @param  mixed  $userId
     * @param  string  $name
     * @param  array  $scopes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function make($userId, $name, array $scopes = []): PersonalAccessTokenResult
    {
        $response = $this->dispatchRequestToAuthorizationServer(
            $this->createRequest($this->clients->personalAccessClient(), $userId, $scopes)
        );

//        $device = $this->getDevice($userId);
        $device = null;

        $token = tap($this->findAccessToken($response), function ($token) use ($userId, $name, $device) {
            $data = ['user_id' => $userId, 'name' => $name];
            if (isset($device)) {
                $data = array_merge($data, ['connected_device_id' => $device->id]);
            }
            $this->tokens->save($token->forceFill($data));
        });

        return new PersonalAccessTokenResult(
            $response['access_token'], $token
        );
    }

    /**
     * @param mixed $userId
     * @return Model|null
     */
    private function getDevice(mixed $userId): Model|null
    {
        $agent = new \Jenssegers\Agent\Agent();
        $device = $agent->device();
        $platform = $agent->platform();
        $browser = $agent->browser();
        $request = request();

        return ConnectedDevice::updateOrCreate([
            'user_id' => $userId,
            'ip' => $request->ip(),
            'device' => $device ?: null,
        ], [
            'finger_print' => $request->fingerprint(),
            'device' => $device ?: null,
            'platform' => $platform ?: null,
            'browser' => $browser ?: null,
            'is_bot' => $agent->isRobot(),
        ]);
    }
}
