<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyFaqs
{
    /**
     * @return MorphMany
     */
    public function faqs(): MorphMany;
}
