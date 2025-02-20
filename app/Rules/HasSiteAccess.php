<?php

namespace App\Rules;

use App\Modules\Setting\Models\Site;
use Illuminate\Contracts\Validation\Rule;

class HasSiteAccess implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return $value && is_string($value) && Site::makingRequest()->hasAccess()->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "You don't have access to the site submitted";
    }
}
