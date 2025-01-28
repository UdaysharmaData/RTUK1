<?php

namespace Database\Seeders;

Use DB;
use Str;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use App\Modules\User\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The permission seeder logs');

        $this->truncateTable();

        $permissions = DB::connection('mysql_2')->table('permissions')->get();

        foreach ($permissions as $permission) {
            Permission::factory()
                ->create([
                    'id' => $permission->id,
                    'ref' => Str::orderedUuid(),
                    'name' => $permission->name,
                    'description' => $permission->description
                ]);
        }

        // Update the names of these permissions
        Permission::where('name', 'can_manage_signups')->update(['name' => 'can_manage_enquiries', 'description' => 'Allows the user to manage enquiries']);
        Permission::where('name', 'can_manage_charity_signups')->update(['name' => 'can_manage_charity_enquiries', 'description' => 'Allows the user to manage charity enquiries']);

        // Add new permissions to the application
        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_regions',
                'description' => 'Allows the user to manage regions'
            ]);

        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_partner_channels',
                'description' => 'Allows the user to manage partner channels'
            ]);

        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_external_enquiries',
                'description' => 'Allows the user to manage external enquiries'
            ]);

        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_participants',
                'description' => 'Allows the user to manage participants'
            ]);

        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_offer_place_to_events',
                'description' => 'Allows the user to offer places to events'
            ]);

        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_sponsors',
                'description' => 'Allows the user to manage sponsors'
            ]);
 
        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_series',
                'description' => 'Allows the user to manage series'
            ]);

        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_venues',
                'description' => 'Allows the user to manage venues'
            ]);

        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_cities',
                'description' => 'Allows the user to manage cities'
            ]);

        Permission::factory()
            ->create([
                'ref' => Str::orderedUuid(),
                'name' => 'can_manage_medals',
                'description' => 'Allows the user to manage medals'
            ]);
    }

    /**
     * Truncates the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        Permission::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
