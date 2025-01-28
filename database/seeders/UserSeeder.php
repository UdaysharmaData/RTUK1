<?php

namespace Database\Seeders;

Use DB;
use Str;
use File;
use Schema;
use Storage;
use Carbon\Carbon;
use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\GenderEnum;
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
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\User\Models\ParticipantProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    use WithoutModelEvents, EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The user seeder logs');

        $this->truncateTables();

        $users = DB::connection('mysql_2')->table('users')->get();

        foreach ($users as $user) {
            // NB: Some users don't have roles assigned to them in the system. Please check what to do for these users.
            // TODO: Either assign a default role to them or not create these users or create them and provide a menu where the admin can query these users and assign roles to them.

            $_user = User::factory()
                ->create([
                    'id' => $user->id,
                    'ref' => Str::orderedUuid(),
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $this->valueOrDefault($user->phone),
                    // 'password' => $user->password,
                    // 'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                    'password' => $password = '$2y$10$TuEsN6GkDAzUpo.u3DTsLufO8/y8693a0NKzU5ku7QnGLSGgrHGLa', // Password.0!
                    'temp_pass' => $user->temp_pass,
                    'email_verified_at' => Carbon::now()->subDay(),
                    // 'remember_token' => $user->remember_token, // no need to save this. A new authentication is required
                ]);

            $_user->createPasswordRecord($password);


            $gender = $this->valueOrDefault($user->gender);
            $gender = ($gender == "senior women" ? GenderEnum::Female->value : GenderEnum::tryFrom($gender)?->value);

            // Create the user profile
            $profile = Profile::factory()
                ->for($_user)
                ->create([
                    'ref' => Str::orderedUuid(),
                    'gender' => $gender,
                    // 'dob' => $this->valueOrDefault($user->dob) ? Carbon::parse($user->dob)->format('d-m-Y') : null, // Uncomment this line while David would have fixed the issue with the dob mutator (does not handle the case of null values)
                    // 'dob' => $this->valueOrDefault($user->dob) ? Carbon::parse($user->dob)->format('d-m-Y') : Carbon::now()->format('d-m-Y'),
                ]);

            if ($this->valueOrDefault($user->profile_picture) && Storage::disk('sfc')->exists($user->profile_picture)) { // Copy the image from the sport-for-api disk if it exists
                $upload = $profile->upload()->updateOrCreate([], [
                    'ref' => Str::orderedUuid(),
                    'url' => config('app.images_path') . str_replace('uploads/', '', $user->profile_picture),
                    'title' => $_user->full_name,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Image
                ]);

                Storage::disk('local')->put('public'.$upload->real_path, Storage::disk('sfc')->get($user->profile_picture));
            }

            // Assign the user to it's role (Users currently have just one role)
            $role = $this->getRole($user->role_id);
            // $role = Role::find($user->role_id);
            $roleUser = null;

            if ($role) { // The role will be created only for users currently having a role on the previous database. For those not having one, the admin can assign one to them by updating their profile.
                $roleUser = RoleUser::where('role_id', $role->id)
                    ->where('user_id', $_user->id)
                    ->first();

                if (! $roleUser) {
                    $roleUser = RoleUser::factory()
                        ->for($_user)
                        ->for($role)
                        ->create();
                }

                if ($_user->email != 'matt@runthrough.co.uk') { // The active role of this user is set to administrator in the logic below
                    $_user->activeRole()->create([
                        'role_id' => $roleUser->role_id
                    ]);
                }
            }

            // ONLY THE GENERAL AND WEBSITE ADMINISTRATORS SHOULD BE ASSIGNED TO A SITE

            // $roleMatch = $user->role_id ? $this->checkRole($user->role_id, 'virtual_administrator') : false;

            // if (($site = Site::where('domain', 'virtualmarathonseries.com')->first()) && $roleMatch) { // Assign the virtual administrator to it's site
            //     SiteUser::factory()
            //         ->for($_user)
            //         ->for($site)
            //         ->create([
            //             'ref' => Str::orderedUuid()
            //         ]);
            // }

            // $roleMatch = $user->role_id ? $this->checkRole($user->role_id, 'rankings_administrator') : false;

            // if (($site = Site::where('domain', 'runthroughhub.com')->first()) && $roleMatch) { // Assign the rankings administrator to it's site
            //     SiteUser::factory()
            //         ->for($_user)
            //         ->for($site)
            //         ->create([
            //             'ref' => Str::orderedUuid()
            //         ]);
            // }

            if ($roleUser?->role->name == RoleNameEnum::Administrator) { // Assign the administrator to all the sites
                foreach (Site::all() as $site) {                           // TODO: Update this foreach to ensure the users with the Administrator role gets assigned to the rfc,sfc,cfc sites.
                // foreach (Site::whereIn('domain', ['sportforcharity.com', 'runforcharity.com', 'cycleforcharity.com']) as $site) {
                    SiteUser::factory()
                        ->for($_user)
                        ->for($site)
                        ->create([
                            'ref' => Str::orderedUuid()
                        ]);
                }
            }

            if ($roleUser?->role->name == RoleNameEnum::Participant) { // Create a participant profile if the user is a participant
                if ($user->fundraising_url || $user->slogan || $user->club) { // Participants will rarely fill this record. So, no need to have it created with null values. This helps to make the database lighter with only useful data
                    ParticipantProfile::factory()
                        ->for($profile)
                        ->create([
                            'ref' => Str::orderedUuid(),
                            'fundraising_url' => $user->fundraising_url,
                            'slogan' => $user->slogan,
                            'club' => $user->club
                        ]);
                }
            }
        }

        if ($user = User::where('email', 'matt@runthrough.co.uk')->first()) {
            RoleUser::updateOrCreate([ // Add the admin role to this user
                'role_id' => Role::where('name', RoleNameEnum::Administrator->value)->value('id'),
                'user_id' => $user->id,
            ]);

            if ($site = Site::where('domain', SiteEnum::RunThroughHub->value)->first()) {
                SiteUser::updateOrCreate([ // Grant access the the Runthrough site
                    'site_id' => $site->id,
                    'user_id' => $user->id,
                    'ref' => Str::orderedUuid()
                ]);
            }

            ActiveRole::updateOrCreate([ // Update the active role
                'role_id' => Role::where('name', RoleNameEnum::Administrator->value)->value('id'),
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Get the role of the user.
     *
     * @param  ?int $id
     * @return ?Role
     */
    private function getRole(?int $id = null): ?Role
    {
        $role = DB::connection('mysql_2')->table('roles')->where('id', $id)->first();

        if (! $role) return Role::where('name', RoleNameEnum::Participant->value)->first();

        $roleName = null;

        switch ($role->name) { // Get rid of the virtual_participant, virtual_administrator & rankings_administrator roles
            case 'virtual_participant':
                $roleName = RoleNameEnum::Participant->value;

                break;
            case 'virtual_administrator':
            case 'rankings_administrator':
                $roleName = RoleNameEnum::Administrator->value;

                break;
            default:
                $roleName = $role->name;
        }

        return Role::where('name', $roleName)->first();
    }

    /**
     * Check role
     *
     * @param  int $id
     * @param  string $name
     * @return bool
     */
    private function checkRole(int $id, string $name): bool
    {
        $role = DB::connection('mysql_2')->table('roles')->where('id', $id)->first();

        if (!$role) return false;

        if ($role->name == $name) {
            return true;
        }

        return false;
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
        ActiveRole::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
