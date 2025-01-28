<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\UpdateUserPasswordRequest;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UpdateUserPassword extends Controller
{
    use Response;


    /**
     * Update Password
     *
     * A user can update their password.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam old_password string required Specifies user's current password. Example: oldPASSword12@
     * @bodyParam new_password string required Specifies user's proposed password. Example: newPASSword123@
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param UpdateUserPasswordRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateUserPasswordRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        try {
            $user->update([
                'password' => Hash::make($data['new_password'])
            ]);

            return $this->success('Password Updated.', 201, []);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception->getMessage());
            return $this->error("Oops...We couldn't find this user you were trying to update.", 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update password.', 400);
        }
    }
}
