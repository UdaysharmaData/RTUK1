<?php

namespace App\Modules\User\Controllers\Actions;

use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\UpdateUserPersonalInfoRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserPersonalInfo extends Controller
{
    use Response;

    /**
     * Update Personal Info
     *
     * Allows User/Admin to Update their/user's personal info.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam first_name string required The first name of the user. Example: Wendy
     * @bodyParam last_name string required The last name of the user. Example: Mike
     * @bodyParam email string required The email of the User. Example: user@email.com
     * @bodyParam phone string required The phone number of the User. Example: +12333333333
     * @bodyParam gender string The gender of the User. Example: female
     * @bodyParam dob date The date of birth of the User. Example: 2000-12-31
     * @bodyParam country string The user's country of residence. Example: Nigeria
     * @bodyParam state string The user's state of residence. Example: Lagos
     * @bodyParam city string The user's city of residence. Example: Ikeja
     * @bodyParam postcode string The user's postcode. Example: 100271
     * @bodyParam address string The user's address. Example: 1, Lagos Road, Ikeja
     * @bodyParam nationality string The user's nationality. Example: British
     * @bodyParam passport_number string The user's passport number. Example: 12347474686
     * @bodyParam occupation string The user's occupation. Example: Engineering
     * @bodyParam tshirt_size string The user's tshirt size. Example: xl
     * @bodyParam emergency_contact_name string The user's emergency contact's name. Example: Peter Parker
     * @bodyParam emergency_contact_phone string The user's emergency contact's phone number. Example: 09012345678
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param UpdateUserPersonalInfoRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateUserPersonalInfoRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use($data, $user) {
                $user->update([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                ]);

                $profile = tap($user->profile)->update([
                    'gender' => $data['gender'] ?? null,
                    'dob' => $data['dob'] ?? null,
                    'country' => $data['country'] ?? null,
                    'state' => $data['state'] ?? null,
                    'city' => $data['city'] ?? null,
                    'postcode' => $data['postcode'] ?? null,
                    'address' => $data['address'] ?? null,
                    'nationality' => $data['nationality'] ?? null,
                    'occupation' => $data['occupation'] ?? null,
                    'passport_number' => $data['passport_number'] ?? null,
                    'bio' => $data['bio'] ?? null,
                ]);

                $profile->participantProfile()->updateOrCreate(
                    ['profile_id' => $profile->id],
                    array_filter([
                        'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                        'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                        'slogan' => $data['slogan'] ?? null,
                        'club' => $data['club'] ?? null,
                        'tshirt_size' => ParticipantProfileTshirtSizeEnum::tryFrom($data['tshirt_size'] ?? null)?->value
                    ])
                );

                CacheDataManager::flushAllCachedServiceListings(new UserDataService());

                return $this->success("User's personal info has been Updated.", 201, [
                    'user' => $user->fresh()->load('profile.participantProfile'),
                ]);
            }, 5);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops...We couldn't find this user you were trying to update.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to update personal info.', 400);
        }
    }
}
