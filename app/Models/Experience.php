<?php

namespace App\Models;

use App\Traits\BelongsToSite;
use App\Modules\User\Models\User;
use App\Traits\AddUuidRefAttribute;
use App\Modules\Event\Models\Event;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\AddRequestUserAttribute;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FilterableListQueryScope;
use App\Traits\SiteIdAttributeGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\Drafts\DraftTrait;

class Experience extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        DraftTrait,
        SoftDeletes,
        BelongsToSite,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        AddRequestUserAttribute,
        SiteIdAttributeGenerator,
        FilterableListQueryScope;

    /**
     * @var string[]
     */
    protected $fillable = ['name', 'icon', 'values'];

    /**
     * @var string[]
     */
    protected $casts = [
        'values' => 'array'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the experience permanently will unlink it from events and others. This action is irreversible.'
    ];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'values' => ['required', 'array', 'min:1'],
            'values.*' => ['string', 'max:100'],
            'icon' => ['required', 'string', 'max:100'],
        ]
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function events(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /**
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }
}
