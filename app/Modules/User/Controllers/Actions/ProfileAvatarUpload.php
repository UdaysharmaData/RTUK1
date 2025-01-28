<?php

namespace App\Modules\User\Controllers\Actions;

use App\Enums\UploadUseAsEnum;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\Profile;
use App\Modules\User\Requests\ProfileAvatarRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Services\FileManager\UploadFileManager;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProfileAvatarUpload extends Controller
{
    use Response, UploadModelTrait;


    /**
     * Profile Picture
     *
     * Update a user's profile display picture.
     *
     * @group Profile
     * @authenticated
     * @header Content-Type multipart/form-data
     *
     * @bodyParam avatar string required The image Data URI
     *
     * @param ProfileAvatarRequest $request
     * @param string $profile
     * @return JsonResponse
     */
    public function __invoke(ProfileAvatarRequest $request, string $profile): \Illuminate\Http\JsonResponse
    {
        try {
            $profile = Profile::query()
                ->withoutEagerLoads()
                ->where('ref', '=', $profile)
                ->firstOrFail();

            abort_if($profile->user_id !== auth()->id(), 403, 'You are not authorized to update this user\'s profile image.');

            $upload = (new UploadFileManager())->upload($request->avatar, []);

            $this->attachSingleUploadToModel($profile, $upload->ref, UploadUseAsEnum::Avatar, true);

            CacheDataManager::flushAllCachedServiceListings(new UserDataService);

            $user = $profile->user->withoutRelations()->load(['activeRole', 'roles', 'profile']);

            return $this->success('Successfully updated your profile picture.', 201, [
                'user' => $user
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception->getMessage());

            return $this->error("Oops! We couldn't find the user whose profile picture you are trying to update.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while trying to update your profile picture.', 400);
        }
    }
}
