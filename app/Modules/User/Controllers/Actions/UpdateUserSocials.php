<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\UpdateUserSocialsRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class UpdateUserSocials extends Controller
{
    use Response;

    /**
     * Socials
     *
     * Allows User/Admin to Update their/user's socials info.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam socials string[] Associative array of key-value pairs of platform and url. Example: [{"platform": "facebook", "url": "https://facebook.com/me"}]
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param UpdateUserSocialsRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateUserSocialsRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        try {
            if (isset($data['socials'])) {
                foreach ($data['socials'] as $social) {
                    $user->socials()->updateOrCreate(
                        ['platform' => $social['platform']],
                        ['url' => $social['url']]
                    );
                }
            }

            CacheDataManager::flushAllCachedServiceListings(new UserDataService);

            return $this->success("Socials info has been Updated.", 201, [
                'socials' => $user->socials,
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops...We couldn't find this user's info.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to update your socials.', 400);
        }
    }
}
