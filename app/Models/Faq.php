<?php

namespace App\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;

use App\Traits\BelongsToSite;
use App\Traits\AddUserIdAttribute;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\SiteIdAttributeGenerator;

class Faq extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, SiteIdAttributeGenerator, BelongsToSite, AddUserIdAttribute;

    /**
     * @var string[]
     */
    protected $fillable = ['user_id', 'section', 'description'];

    /**
     * @var string[]
     */
    protected $with = ['faqDetails'];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'section' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255']
        ]
    ];

    /**
     * @return HasMany
     */
    public function faqDetails(): HasMany
    {
        return $this->hasMany(FaqDetails::class);
    }

    /**
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * @return MorphTo
     */
    public function faqsable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Delete (Cascade) the associated faq details
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::deleted(function ($model) {
            foreach ($model->faqDetails as $faqDetail) {
                $faqDetail->delete();
            }
        });
    }
}
