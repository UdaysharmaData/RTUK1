<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Report extends Model
{
    use HasFactory;
    protected $fillable = [
        'site_name',
        'site_id',
        'event_name',
        'event_id',
        'event_category_id',
        'event_category_name',
        'total_converted_till_date',
        'total_ldt_count',
        'total_converted_current',
        'total_failed_current',
        'total_failed_till_date',
        'ldt_event_name',
        'ldt_race_id',
        'ldt_occurrence_id',
    ];
}