<?php

namespace App\Models;

use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddUuidRefAttribute;
use App\Traits\Uploadable\HasManyUploads;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaLibraryCollection extends Model implements CanUseCustomRouteKeyName, CanHaveManyUploadableResource
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, HasManyUploads;

    /**
     * @var string[]
     */
    protected $fillable = ['name', 'description'];

    /**
     * @var string[]
     */
    protected $with = ['uploads'];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500']
        ]
    ];

    /**
     * @return BelongsTo
     */
    public function mediaLibrary(): BelongsTo
    {
        return $this->belongsTo(MediaLibrary::class);
    }
}
