<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use App\Enums\RoleNameEnum;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    use EmptySpaceToDefaultData, WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
       $this->truncateTables();

        // $this->seedRoles();

       Log::channel('dataimport')->debug('The role seeder logs');

       $roles = DB::connection('mysql_2')->table('roles')->whereNotIn('name', ['virtual_participant', 'virtual_administrator', 'rankings_administrator'])->get(); // Don't create these roles

       foreach ($roles as $role) {
            $_role = Role::factory()
                ->create([
                    'id' => $role->id,
                    'ref' => Str::orderedUuid(),
                    'name' => $role->name,
                    'description' => $this->valueOrDefault($role->description, Str::title(Str::replace('_', ' ', $role->name)))
                ]);
       }
    }

    protected function seedRoles()
    {
        Role::createDefaults();
    }

    /**
     * Truncates the table
     *
     * @return void
     */
    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        Role::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
