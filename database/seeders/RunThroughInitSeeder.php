<?php

namespace Database\Seeders;

Use DB;
use Str;
use File;
use Schema;
use Storage;
use Carbon\Carbon;
use App\Models\ApiClient;
use App\Enums\RoleNameEnum;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Modules\User\Models\Profile;
use App\Modules\Setting\Models\Site;
use App\Modules\User\Models\SiteUser;
use App\Modules\User\Models\RoleUser;
use App\Modules\User\Models\ActiveRole;
use App\Modules\User\Models\Permission;
use App\Modules\User\Models\PermissionUser;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\User\Models\ParticipantProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RunThroughInitSeeder extends Seeder
{
    use WithoutModelEvents, EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The runthrough clients & users seeder logs');

        $this->truncateTables();

        // $site = Site::create([ // RunThrough site
        //     'ref' => Str::orderedUuid(),
        //     'domain' => 'runthrough.co.uk',
        //     'name' => 'RunThrough',
        //     'code' => 'runthrough',
        //     'status' => 1
        // ]);

        $site = Site::where('domain', 'runthrough.co.uk')->first();

        $apiClient = ApiClient::create([ // RunThrough portal/website client
            'api_client_id' => Str::orderedUuid(),
            'site_id' => $site->id,
            'name' => $site->name." Events",
            'host' =>  'www.runthrough.co.uk',
            'ip' => null,
            // 'blacklist' => [],
            'is_active' => 1,
        ]);

        /*ApiClient::create([ // RunThrough Hub client
            'api_client_id' => Str::orderedUuid(),
            'site_id' => $site->id,
            'name' => $site->name.' Hub',
            'host' =>  'hub.runthrough.co.uk',
            'ip' => null,
            // 'blacklist' => [],
            'is_active' => 1,
        ]);*/   

        $users = DB::connection('mysql_2')->table('users')->whereIn('email', ['matt@runthrough.co.uk', 'mark@runforcharity.com', 'norberth.t@gmail.com'])->get();

        foreach ($users as $user) {
            if ($user) { // Create the user
                $_user = User::factory()
                    ->create([
                        'api_client_id' => $apiClient->id,
                        'ref' => Str::orderedUuid(),
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $this->valueOrDefault($user->phone),
                        'password' => \Hash::make('Password.0!'),
                        'temp_pass' => $user->temp_pass,
                        'email_verified_at' => Carbon::now()->subDay(),
                    ]);

                // Create the user profile
                $profile = Profile::factory()
                    ->for($_user)
                    ->create([
                        'ref' => Str::orderedUuid(),
                        'gender' => $this->valueOrDefault($user->gender),
                        // 'dob' => $this->valueOrDefault($user->dob) ? Carbon::parse($user->dob)->format('d-m-Y') : null, // Uncomment this line while David would have fixed the issue with the dob mutator (does not handle the case of null values)
                        // 'dob' => $this->valueOrDefault($user->dob) ? Carbon::parse($user->dob)->format('d-m-Y') : Carbon::now()->format('d-m-Y'),
                    ]);

                if ($this->valueOrDefault($user->profile_picture) && Storage::disk('sfc')->exists($user->profile_picture)) { // Copy the image from the sport-for-api disk if it exists
                    $url = config('app.images_path') . str_replace('uploads/', '', $user->profile_picture);

                    $profile->upload()->updateOrCreate([], [
                        'ref' => Str::orderedUuid(),
                        'url' => $url,
                        'title' => $_user->full_name,
                        'type' => UploadTypeEnum::Image,
                        'use_as' => UploadUseAsEnum::Image
                    ]);
    
                    Storage::disk('public')->put($url, Storage::disk('sfc')->get($user->profile_picture));
                }

                if ($user->email == 'norberth.t@gmail.com') { // Assign the user to the participant role
                    $role = Role::where('name', 'participant')->first();

                    // Assign the user to the participant role
                    RoleUser::factory()
                        ->for($_user)
                        ->for($role)
                        ->create();

                        $_user->activeRole()->create([ // Set the user's active role
                            'role_id' => $role->id
                        ]);
    
                        // Create a participant profile if the user is a participant
                        if ($user->fundraising_url || $user->slogan || $user->club) { // Participants will rarely fill this record. So, no need to have it created with null values. This helps to make the database lighter with only useful data
                            ParticipantProfile::factory()
                                ->for($profile)
                                ->create([
                                    'fundraising_url' => $user->fundraising_url,
                                    'slogan' => $user->slogan,
                                    'club' => $user->club
                                ]);
                        }
                } else { // Assign the user to the administrator role
                    $role = Role::where('name', 'administrator')->first();

                    // Assign the user to the administrator role
                    RoleUser::factory()
                        ->for($_user)
                        ->for($role)
                        ->create();

                    $_user->activeRole()->create([ // Set the user's active role
                        'role_id' => $role->id
                    ]);

                    // Assign the runthrough admin to the Runthrough site
                    SiteUser::factory()
                        ->for($_user)
                        ->for($site)
                        ->create([
                            'ref' => Str::orderedUuid()
                        ]);
                }

                // Assign the user to the permissions of its role
                $this->assignPermissions($_user);
            }
        }
    }

    /**
     * Assign a user to the permissions of its role
     *
     * @param  User  $user
     * @return void
     */
    private function assignPermissions(User $user): void
    {
        foreach ($user->roles as $role) {
            // $user->permissions()->sync($role->permissions->pluck('id')->all()); // Not necessary given that the PermissionRole model will be removed as it is not needed anymore

            $_role = DB::connection('mysql_2')->table('roles')->where('name', $role->name)->first();

            if ($_role) {
                $permissionsIds = explode(',', $_role->permissions);

                if (count($permissionsIds) > 0 && $this->valueOrDefault($permissionsIds[0])) {
                    $user->permissions()->sync($permissionsIds);
                }
            }

            if ($role?->name == RoleNameEnum::Administrator) {
                $this->assignExtraPermissionsToAdministratorRole($user);
            }
        }
    }

    /**
     * Assign extra permissions to users with the administrator role
     *
     * @param  User  $user
     * @return void
     */
    private function assignExtraPermissionsToAdministratorRole(User $user): void
    {
        // Add the can_manage_market permission to the administrator role
        $permission = Permission::where('name', 'can_manage_market')->first();
        $permission = $permission ?? Permission::factory()->create(['name' => 'can_manage_market']);
        $user->permissions()->attach($permission->id);

        // Add the can_manage_regions permission to the administrator role
        $permission = Permission::where('name', 'can_manage_regions')->first();
        $permission = $permission ?? Permission::factory()->create(['name' => 'can_manage_regions']);
        $user->permissions()->attach($permission->id);
    }

    /**
     * Truncate the tables
     *
     * @return void
     */
    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        User::truncate();
        Profile::truncate();
        RoleUser::truncate();
        SiteUser::truncate();
        // Site::truncate();
        // ApiClient::truncate();
        PermissionUser::truncate();
        ActiveRole::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
