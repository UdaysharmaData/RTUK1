<?php

namespace App\Modules\User\Controllers;

use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Modules\User\Models\Profile;
use App\Services\FileManager\FileManager;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class ProfileController
{
    use Response;

    /**
     * @param Profile $profile
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Profile $profile): \Illuminate\Http\JsonResponse
    {
        return $this->success([
            'profile' => $profile
        ]);
    }

    /**
     * @param Request $request
     * @param Profile $profile
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Profile $profile): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(Profile::RULES['create_or_update']);

        try {
            $profile->update($validated);

            return $this->success([
                'profile' => $profile
            ], 201, 'Your profile has been updated!');
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update your profile', 400);
        }
    }

    public function avatarUpload(Request $request, Profile $profile): \Illuminate\Http\JsonResponse
    {

    }
}
