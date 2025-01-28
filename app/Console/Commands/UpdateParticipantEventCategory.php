<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateParticipantEventCategory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'participant:update-event-category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
        try {

            // Create the temporary table if not exists
            DB::statement('CREATE TABLE IF NOT EXISTS temp_participants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                participant_id INT NOT NULL,
                event_event_category_id INT NOT NULL
            )');

            DB::transaction(function () {
                // Fetch participants with mismatched event categories
                $participants = DB::select('
            SELECT eec2.id AS event_event_category_id,fmq.event_id,fmq.participant_id,fmq.event_category_id,fmq.par,fmq.main_event_category_id AS wrong_id,fmq.id FROM (SELECT *
            FROM (
                SELECT
                    ee.site_id,
                    ee.event_category_event_third_party_id,
                    ee.event_id,
                    ee.participant_id,
                    ecetp.id AS ecetp_id,
                    ecetp.event_category_id,
                    p.id AS par,
                    p.event_event_category_id,
                    eec.id,
                    eec.event_category_id AS main_event_category_id
                FROM
                    external_enquiries ee
                JOIN
                    event_category_event_third_party ecetp
                    ON ecetp.id = ee.event_category_event_third_party_id
                LEFT JOIN
                    participants p
                    ON p.id = ee.participant_id
                LEFT JOIN
                    event_event_category eec
                    ON eec.id = p.event_event_category_id
            ) subquery
            WHERE subquery.event_category_id != subquery.main_event_category_id) AS fmq
            JOIN
            event_event_category eec2
            ON eec2.event_id = fmq.event_id AND eec2.event_category_id=fmq.event_category_id
            ');

                // Insert data into the temporary table
                foreach ($participants as $participant) {
                    DB::table('temp_participants')->insert([
                        'participant_id' => $participant->participant_id,
                        'event_event_category_id' => $participant->event_event_category_id,
                    ]);
                }

                $this->info('Data successfully inserted into the temporary table.');

                // Fetch data from the temporary table for further processing
                $update_participants = DB::table('temp_participants')
                    ->select('participant_id', 'event_event_category_id')
                    ->get();
                foreach ($update_participants as $parti) {
                    DB::table('participants')
                        ->where('id', $parti->participant_id)
                        ->update(['event_event_category_id' => $parti->event_event_category_id]);
                }

            });

            $this->info('All operations completed successfully.');
        } catch (\Exception $e) {
            // Handle exceptions and log the error
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Error in handling participants mismatch: ', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

}
