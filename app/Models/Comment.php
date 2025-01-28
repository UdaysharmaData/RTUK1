<?php

namespace App\Models;

use App\Contracts\CanHaveManyLikes;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\User;
use App\Traits\AddUuidRefAttribute;
use App\Traits\HasManyLikes;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Comment extends Model implements CanUseCustomRouteKeyName, CanHaveManyLikes
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, HasManyLikes;

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'body' => ['required', 'string', 'max:200']
        ]
    ];

    /**
     * @var string[]
     */
    protected $fillable = ['user_id', 'body'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    /**
     * @return MorphTo
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get user-friendly created_at
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->diffForHumans(),
        );
    }
}
