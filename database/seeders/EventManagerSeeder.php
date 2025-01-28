<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\EventManager;
use App\Enums\EventManagerCompleteNotificationsEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event manager seeder logs');

        $this->truncateTable();

        // Get users of role event_manager and create a record for each on the event_managers table.
        $roles = DB::connection('mysql_2')->table('roles')->where('name', 'event_manager')->pluck('id');
        $users = DB::connection('mysql_2')->table('users')->whereIn('role_id', $roles)->get();
        
        foreach ($users as $user) {
            $_user = User::find($user->id);

            EventManager::factory()
                ->for($_user ?? User::factory()->create(['id' => $user->id]))
                ->create([
                    'complete_notifications' => EventManagerCompleteNotificationsEnum::Monthly
                ]);

            if (!$_user) {
                Log::channel('dataimport')->debug("id: {$user->id} The user id  {$user->id} did not exists and was created. Event_manager: ".json_encode($user));
            }
        }
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        EventManager::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
