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

class ProfileBackgroundImageDelete extends Controller
{
    use Response;

    /**
     * Delete Profile Background Picture
     *
     * Delete a user's profile background picture.
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
            $bgQuery = $profile->backgroundImage->upload()
                ->where('use_as', UploadUseAsEnum::ProfileBackgroundImage);

            if (! is_null($bg = $bgQuery->first())) {
                if (isset($bg->url)) {
                    Storage::disk(config('filesystems.default'))->delete($bg->url)
                    && $bg->delete();
                }
            }

            CacheDataManager::flushAllCachedServiceListings(new UserDataService);

            $user = $profile->user->withoutRelations()->load(['activeRole', 'roles']);
            $user->profile = $profile->withoutRelations();

            return $this->success('Successfully deleted the background image.', 201, [
                'user' => $user
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops! We couldn't find the user whose background image you are trying to delete.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to delete your background image.', 400);
        }
    }
}
