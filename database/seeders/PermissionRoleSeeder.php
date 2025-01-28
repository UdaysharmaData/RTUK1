<?php

namespace Database\Seeders;

use App\Enums\RoleNameEnum;
Use DB;
use Schema;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Modules\User\Models\Permission;
use App\Modules\User\Models\PermissionRole;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionRoleSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The permission seeder logs');

        $this->truncateTable();

        $roles = DB::connection('mysql_2')->table('roles')->whereNotIn('name', ['virtual_participant', 'virtual_administrator', 'rankings_administrator'])->get(); // Don't create these roles;

        foreach ($roles as $role) {
            if ($this->valueOrDefault($role->permissions)) {
                foreach (explode(',', $role->permissions) as $permission_id) {
                    if ($this->valueOrDefault($permission_id)) {
                        $permission = Permission::find($permission_id);
                        $_role = Role::find($role->id);

                        PermissionRole::factory()
                            ->for($permission ?? Permission::factory()->create(['id' => $permission_id]))
                            ->for($_role ?? Role::factory()->create(['id' => $role->id, 'name' => $role->name]))
                            ->create();

                        if (!$permission) {
                            Log::channel('dataimport')->debug("id: {$role->id} The permission id  {$permission_id} did not exists and was created. Permission_role: ".json_encode($role));
                        }

                        if (!$_role) {
                            Log::channel('dataimport')->debug("id: {$role->id} The role id  {$role->id} did not exists and was created. Permission_role: ".json_encode($role));
                        }
                    }
                }
            }
        }

        $adminRole = Role::where('name', RoleNameEnum::Administrator)->first() ?? Role::factory()->create(['name' => RoleNameEnum::Administrator->value]);
        $developerRole = Role::where('name', RoleNameEnum::Developer)->first() ?? Role::factory()->create(['name' => RoleNameEnum::Developer->value]);
        $accountManager = Role::where('name', RoleNameEnum::AccountManager)->first() ?? Role::factory()->create(['name' => RoleNameEnum::AccountManager->value]);
        $charityRole = Role::where('name', RoleNameEnum::Charity)->first() ?? Role::factory()->create(['name' => RoleNameEnum::Charity->value]);
        $charityUserRole = Role::where('name', RoleNameEnum::CharityUser)->first() ?? Role::factory()->create(['name' => RoleNameEnum::CharityUser->value]);
        $eventManager = Role::where('name', RoleNameEnum::EventManager)->first() ?? Role::factory()->create(['name' => RoleNameEnum::EventManager->value]);
        $corporate = Role::where('name', RoleNameEnum::Corporate)->first() ?? Role::factory()->create(['name' => RoleNameEnum::Corporate->value]);
        $runThroughData = Role::where('name', RoleNameEnum::RunthroughData)->first() ?? Role::factory()->create(['name' => RoleNameEnum::RunthroughData->value]);

        $roles = [$adminRole, $developerRole, $charityRole];
        $permission = Permission::where('name', 'can_manage_market')->first();
        
        foreach ($roles as $role) {
            $record = PermissionRole::where('permission_id', $permission?->id)
                ->where('role_id', $role?->id);

            if ($record->doesntExist()) { // Add the can_manage_market permission to the role
                PermissionRole::factory()
                    ->for($permission ?? Permission::factory()->create(['name' => 'can_manage_market']))
                    ->for($role)
                    ->create();
            }
        }

        $roles = [$adminRole, $developerRole];
        $permission = Permission::where('name', 'can_manage_regions')->first();

        foreach ($roles as $role) {
            $record = PermissionRole::where('permission_id', $permission?->id)
                ->where('role_id', $role?->id);

            if ($record->doesntExist()) { // Add the can_manage_regions permission to the role
                PermissionRole::factory()
                    ->for($permission ?? Permission::factory()->create(['name' => 'can_manage_regions']))
                    ->for($role)
                    ->create();
            }
        }

        $roles = [$adminRole, $developerRole];
        $permission = Permission::where('name', 'can_manage_partner_channels')->first();

        foreach ($roles as $role) {
            $record = PermissionRole::where('permission_id', $permission?->id)
                ->where('role_id', $role?->id);

            if ($record->doesntExist()) { // Add the can_manage_partner_channels permission to the role
                PermissionRole::factory()
                    ->for($permission ?? Permission::factory()->create(['name' => 'can_manage_partner_channels']))
                    ->for($role)
                    ->create();
            }
        }

        $roles = [$adminRole, $developerRole, $charityRole, $charityUserRole, $eventManager, $corporate, $runThroughData];
        $permission = Permission::where('name', 'can_manage_external_enquiries')->first();

        foreach ($roles as $role) {
            $record = PermissionRole::where('permission_id', $permission?->id)
                ->where('role_id', $role?->id);

            if ($record->doesntExist()) { // Add the can_manage_external_enquiries permission to the role
                PermissionRole::factory()
                    ->for($permission ?? Permission::factory()->create(['name' => 'can_manage_external_enquiries']))
                    ->for($role)
                    ->create();
            }
        }

        $roles = [$adminRole, $developerRole, $charityRole, $charityUserRole, $eventManager, $corporate, $runThroughData];
        $permission = Permission::where('name', 'can_manage_participants')->first();

        foreach ($roles as $role) {
            $record = PermissionRole::where('permission_id', $permission?->id)
                ->where('role_id', $role?->id);

            if ($record->doesntExist()) { // Add the can_manage_participants permission to the role
                PermissionRole::factory()
                    ->for($permission ?? Permission::factory()->create(['name' => 'can_manage_participants']))
                    ->for($role)
                    ->create();
            }
        }

        $roles = [$adminRole, $developerRole, $charityRole];
        $permission = Permission::where('name', 'can_offer_place_to_events')->first();

        foreach ($roles as $role) {
            $record = PermissionRole::where('permission_id', $permission?->id)
                ->where('role_id', $role?->id);

            if ($record->doesntExist()) { // Add the can_offer_place_to_events permission to the role
                PermissionRole::factory()
                    ->for($permission ?? Permission::factory()->create(['name' => 'can_offer_place_to_events']))
                    ->for($role)
                    ->create();
            }
        }

        $roles = [$adminRole];
        $permissions = ['can_manage_sponsors', 'can_manage_series', 'can_manage_venues', 'can_manage_cities', 'can_manage_medals'];

        foreach ($roles as $role) {
            foreach ($permissions as $_permission) {
                $permission = Permission::where('name', $_permission)->first();
                $record = PermissionRole::where('permission_id', $permission?->id)
                    ->where('role_id', $role?->id);

                if ($record->doesntExist()) { // Add the permission to the role
                    PermissionRole::factory()
                        ->for($permission ?? Permission::factory()->create(['name' => $_permission]))
                        ->for($role)
                        ->create();
                }
            }
        }
    }

    /**
     * Truncates the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        PermissionRole::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
