<?php

namespace App\Modules\User\Controllers\Actions;

use App\Enums\UploadUseAsEnum;
use App\Http\Controllers\Controller;
use App\Models\BackgroundImage;
use App\Modules\User\Models\Profile;
use App\Modules\User\Requests\ProfileBackgroundImageRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Services\FileManager\UploadFileManager;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProfileBackgroundImageUpload extends Controller
{
    use Response,  UploadModelTrait;


    /**
     * Profile Background Image
     *
     * Update a user's profile background image.
     *
     * @group Profile
     * @authenticated
     * @header Content-Type multipart/form-data
     *
     * @bodyParam background_image string required The image Data URI
     *
     * @param ProfileBackgroundImageRequest $request
     * @param string $profile
     * @return JsonResponse
     */
    public function __invoke(ProfileBackgroundImageRequest $request, string $profile): \Illuminate\Http\JsonResponse
    {
        try {
            $profile = Profile::query()
                ->withoutEagerLoads()
                ->where('ref', '=', $profile)
                ->firstOrFail();

            abort_if($profile->user_id !== auth()->id(), 403, 'You are not authorized to update this user\'s background image.');

            $background = BackgroundImage::firstOrCreate([
                'profile_id' => $profile->id
            ], []);

            $upload = (new UploadFileManager())->upload($request->background_image, []);

            $this->attachSingleUploadToModel($background, $upload->ref, UploadUseAsEnum::ProfileBackgroundImage, true);

            CacheDataManager::flushAllCachedServiceListings(new UserDataService);

            $user = $profile->user->withoutRelations()->load('profile')->toArray();
            $user['profile']['background_image']['upload'] = $background->upload;
            $user['profile']['background_image_url'] = $upload->storage_url;

            return $this->success('Successfully updated the background image.', 201, [
                'user' => $user
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops! We couldn't find the user whose background image you are trying to update.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to update your background image.', 400);
        }
    }
}
