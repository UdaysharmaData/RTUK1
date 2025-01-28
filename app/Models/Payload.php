<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payload extends Model
{
    use HasFactory;

    /**
     * mass assignable properties
     * @var string[]
     */
    protected $fillable = [
        'response'
    ];

    /**
     * The attributes that should be cast.
     * @var string[]
     */
    protected $casts = [
        'response' => 'array',
    ];

    /**
     * @param string $payload
     * @return void
     */
    public static function stashAway(string $payload)
    {
        self::create([
            'response' => $payload
        ]);
    }
}
