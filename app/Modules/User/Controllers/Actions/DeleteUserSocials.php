<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\DeleteUserSocialsRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class DeleteUserSocials extends Controller
{
    use Response;

    /**
     * Remove Socials
     *
     * Allows Deletion of socials info.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam platform string The platform for the socials record to be deleted. Example: facebook
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param DeleteUserSocialsRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(DeleteUserSocialsRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        try {
            $user->socials()
                ->where('platform', $data['platform'])
                ->delete();

            CacheDataManager::flushAllCachedServiceListings(new UserDataService);

            return $this->success("Socials info has been deleted.", 201, [
                'socials' => $user->socials,
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops...We couldn't find this user's info.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to delete your socials.', 400);
        }
    }
}
