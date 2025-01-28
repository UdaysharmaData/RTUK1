<?php

namespace App\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uploadable\HasManyUploads;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;

class FaqDetails extends Model implements CanHaveManyUploadableResource
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, HasManyUploads;

    /**
     * @var string[]
     */
    protected $fillable = [
        'question',
        'answer',
        'view_more_link'
    ];

    /**
     * @var string[]
     */
    protected $with = ['uploads'];

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'question' => ['required', 'string'],
            'answer' => ['required', 'string']
        ]
    ];

    /**
     * @return BelongsTo
     */
    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class);
    }
}
