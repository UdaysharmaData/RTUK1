<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResaleNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ResaleNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The resale notification seeder logs');

        $this->truncateTable();

        $notifications = DB::connection('mysql_2')->table('resale_notifications')->get();

        foreach ($notifications as $notification) {
            $_notification = ResaleNotification::factory();

            $charity = Charity::find($notification->charity_id);
            $_notification = $_notification->for($charity ?? Charity::factory()->create(['id' => $notification->charity_id]));

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$notification->id} The charity id {$notification->charity_id} did not exists and was created. Resale_notification: ".json_encode($notification));
            }

            $event = Event::find($notification->event_id);
            $_notification = $_notification->for($event ?? Event::factory()->create(['id' => $notification->event_id]));

            if (!$event) {
                Log::channel('dataimport')->debug("id: {$notification->id} The event id {$notification->event_id} did not exists and was created. Resale_notification: ".json_encode($notification));
            }

            $_notification = $_notification->create([
                'status' => $notification->status
            ]);
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
        ResaleNotification::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
