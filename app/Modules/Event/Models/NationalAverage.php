<?php

namespace App\Modules\Event\Models;

use App\Enums\GenderEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\Relations\NationalAverageRelations;

class NationalAverage extends Model
{
    use HasFactory, AddUuidRefAttribute, UuidRouteKeyNameTrait, NationalAverageRelations;

    protected $table = 'national_averages';

    protected $fillable = [
        'event_category_id',
        'gender',
        'year',
        'time',
    ];

    protected $casts = [
        'gender' => GenderEnum::class
    ];

    // /**
    //  * Process national averages
    //  * TODO: Update this after creating the RaceFile model.
    //  */
    // public static function processNationalAverage($raceFile, $gender)
    // {
    //     $nationalAverage = static::firstOrNew([
    //         'event_category_id' => $raceFile->event->category->id,
    //         'gender' => $gender,
    //         'year' => Carbon::parse($raceFile->event->start_date)->year
    //     ]);

    //     $nationalAverage->time = $raceFile->event->category->calculateRankingsAverage($nationalAverage->year, $nationalAverage->gender);
        
    //     if ($nationalAverage->time) {
    //         $nationalAverage->save();
    //     }
    // }
}
