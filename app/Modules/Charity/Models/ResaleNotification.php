<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Charity\Models\Relations\ResaleNotificationRelations;

class ResaleNotification extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, ResaleNotificationRelations;

    protected $table = 'resale_notifications';

    protected $fillable = [
        'charity_id',
        'event_id',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];
}
