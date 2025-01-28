<?php

namespace App\Modules\User\Controllers\Actions;

use App\Enums\UploadUseAsEnum;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\Profile;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileAvatarDelete extends Controller
{
    use Response;

    /**
     * Delete Profile Picture
     *
     * Delete a user's profile display picture.
     *
     * @group Profile
     * @authenticated
     * @header Content-Type multipart/form-data
     *
     * @param Profile $profile
     * @return JsonResponse
     */
    public function __invoke(Profile $profile): \Illuminate\Http\JsonResponse
    {
        try {
            $uploadQuery = $profile->upload()
                ->where('use_as', UploadUseAsEnum::Avatar);

            if (! is_null($upload = $uploadQuery->first())) {
                if (isset($upload->url)) {
                    Storage::disk(config('filesystems.default'))->delete($upload->url)
                    && $upload->delete();
                }
            }

            CacheDataManager::flushAllCachedServiceListings(new UserDataService);

            $user = $profile->user->withoutRelations()->load(['activeRole', 'roles']);
            $user->profile = $profile->withoutRelations();

            return $this->success('Successfully deleted the profile picture.', 201, [
                'user' => $user
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops! We couldn't find the user whose profile picture you are trying delete.", 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete your profile picture.', 400);
        }
    }
}
