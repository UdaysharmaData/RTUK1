<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use Storage;
use Carbon\Carbon;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Database\Traits\SlugTrait;
use Database\Traits\FormatDate;
use Illuminate\Database\Seeder;
use App\Http\Helpers\RegexHelper;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Enums\PredefinedPartnersEnum;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Partner\Models\Partner;
use App\Modules\Partner\Models\PartnerChannel;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PartnerSeeder extends Seeder
{
    use FormatDate, EmptySpaceToDefaultData, SlugTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The partner seeder logs');

        $this->truncateTable();

        $partners = DB::connection('mysql_2')->table('partners')->get();

        foreach ($partners as $partner) {
            $foreignKeyColumns = [];

            $_partner = Partner::factory();

            $_partner = $_partner->create([
                ...$foreignKeyColumns,
                'name' => $partner->name,
                'slug' => $this->getUniqueSlug(Partner::class, $this->valueOrDefault($partner->url, Str::slug($partner->name))),
                'description' => $partner->description,
                'website' => $partner->website,
                'code' => $partner->code
            ]);

            if ($this->valueOrDefault($partner->image)) { // save the image (logo) path
                $image = $_partner->upload()->updateOrCreate([], [
                    'title' => $_partner->name,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Logo,
                    'url' => config('app.images_path') . str_replace('uploads/', '', $partner->image)
                ]);

                if (Storage::disk('sfc')->exists($partner->image)) { // Copy the image
                    Storage::disk('local')->put('public'.$image->url, Storage::disk('sfc')->get($partner->image));
                }
            }

            if ($_partner->name == "Let's Do This") {
                $_partner->update(['code' => PredefinedPartnersEnum::LetsDoThis->value]); // Update the code to that in the PredefinedPartnersEnum

                $this->createLDTPartnerChannels($_partner);
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
        Partner::truncate();
        PartnerChannel::truncate();
        Schema::enableForeignKeyConstraints();
    }

    private $ldtChannels = [
        // 'RunThrough' => 'runthrough',
        // 'RunThroughMax' => 'runthroughmax',
        'LetsDoThisBespoke' => 'lets-do-this-bespoke',
        'LetsDoThisOwnPlace' => 'lets-do-this-own-place',
        'LetsDoThisAll' => 'lets-do-this-all',
        'LetsDoThisRFC' => 'lets-do-this-rfc',
        'LetsDoThisFlagShip' => 'lets-do-this-flagship'
    ];

    /**
     * Create LDT partner channels
     * 
     * @return void
     */
    private function createLDTPartnerChannels(Partner $partner): void
    {
        $partnerChannels = [];

        foreach ($this->ldtChannels as $key => $channel) {
            $now = Carbon::now();

            $partnerChannels = [...$partnerChannels, [
                'ref' => Str::orderedUuid(),
                'partner_id' => $partner->id,
                'name' => RegexHelper::format($key),
                'code' => $channel,
                'created_at' => $now,
                'updated_at' => $now
            ]];
        }

        PartnerChannel::insert($partnerChannels);
    }
}