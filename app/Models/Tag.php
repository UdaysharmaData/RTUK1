<?php

namespace App\Models;

use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * validation rules for create and update
     */
    const RULES = [
        'create_or_update' => [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable']
        ]
    ];

    /**
     * @var string[]
     */
    protected $fillable = ['name', 'description'];

    /**
     * Get all the posts that are assigned this tag.
     */
    public function articles(): MorphToMany
    {
        return $this->morphedByMany(Article::class, 'taggable');
    }
}
