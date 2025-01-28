<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use App\Enums\CallNoteStatusEnum;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CallNote;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CallNoteSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The call note seeder logs');

        $this->truncateTable();

        $callNotes = DB::connection('mysql_2')->table('call_notes')->get();
        // $callNotes = CallNote::on('mysql_2')->get(); // issue with the backing value ( "" is not a valid backing value for enum)

        foreach ($callNotes as $callNote) {
            $charity = Charity::find($callNote->charity_id);

            CallNote::factory()
                // ->for($callNote->charity)
                ->for($charity ?? Charity::factory()->create(['id' => $callNote->charity_id]))
                ->create([
                    'year' => $callNote->year,
                    'call' => $callNote->call,
                    'note' => $callNote->note,
                    'status' => $this->valueOrDefault($callNote->status, CallNoteStatusEnum::NoAnswer)
                ]);

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$callNote->id}  The charity id  {$callNote->charity_id} did not exists and was created. Call_note: ".json_encode($callNote));
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
        CallNote::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
