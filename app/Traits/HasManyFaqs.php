<?php

namespace App\Traits;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasManyFaqs
{
    /**
     * @return MorphMany
     */
    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqsable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete
     *
     * @return void
     */
    public static function bootHasManyFaqs(): void
    {
        $model = new static;

        if (!in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
            static::deleted(function ($model) {
                foreach ($model->faqs as $faq) {
                    $faq->delete();
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                foreach ($model->faqs as $faq) {
                    $faq->delete();
                }
            });
        }
    }
}
