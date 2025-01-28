<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnalyticsTotalCount extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['total'];

    /**
     * @return MorphTo
     */
    public function countable(): MorphTo
    {
        return $this->morphTo();
    }
}
