<?php

namespace Database\Seeders;

use Str;
use App\Enums\RoleNameEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\Permission;
use App\Modules\User\Models\PermissionRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentLatestSeeder extends Seeder
{
    use WithoutModelEvents, SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([

        ]);

        $adminRole = Role::where('name', RoleNameEnum::Administrator)->first() ?? Role::factory()->create(['name' => RoleNameEnum::Administrator->value]);
        $roles = [$adminRole];
        $permissions = ['can_manage_sponsors', 'can_manage_series', 'can_manage_venues', 'can_manage_cities', 'can_manage_medals'];

        foreach ($roles as $role) {
            foreach ($permissions as $_permission) {
                $permission = Permission::where('name', $_permission)->first();
                $record = PermissionRole::where('permission_id', $permission?->id)
                    ->where('role_id', $role?->id);

                if ($record->doesntExist()) { // Add the permission to the role
                    PermissionRole::factory()
                        ->for($permission ?? Permission::factory()->create(['name' => $_permission, 'ref' => Str::orderedUuid()]))
                        ->for($role)
                        ->create();
                }
            }
        }

        User::chunk(100, function ($users) { // Create profile records for users not having one & Assign the can_manage_registrations to users having the participant role
            foreach ($users as $user) {
                if (! $user->profile) {
                    $user->profile()->create(['ref' => Str::orderedUuid()]);
                }

                $user->grant('can_manage_registrations');
            }
        });
    }
}
