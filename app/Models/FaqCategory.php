<?php

namespace App\Models;

use App\Enums\FaqCategoryNameEnum;
use App\Enums\FaqCategoryTypeEnum;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaqCategory extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, SiteIdAttributeGenerator, BelongsToSite;

    /**
     * @var string[]
     */
    protected $fillable = ['name', 'description', 'type'];

    /**
     * @var string[]
     */
    protected $with = ['faqs'];

    /**
     * @var string[]
     */
    protected $casts = [
        'type' => FaqCategoryTypeEnum::class,
        'name' => FaqCategoryNameEnum::class
    ];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'description' => ['nullable', 'string', 'max:300']
        ]
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function faqs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Faq::class, 'category_id');
    }
}
