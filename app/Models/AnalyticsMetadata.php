<?php

namespace App\Models;

use App\Traits\AddRequestMetaAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnalyticsMetadata extends Model
{
    use HasFactory, AddRequestMetaAttributes;

    /**
     * @var string
     */
    protected $table = 'analytics_metadata';

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'identifier'
    ];

    /**
     * @return MorphTo
     */
    public function metadata(): MorphTo
    {
        return $this->morphTo();
    }
}
