<?php

namespace App\Rules;

use App\Enums\UploadTypeEnum;
use App\Models\Upload;
use Illuminate\Contracts\Validation\Rule;

class EnsureUploadDataExists implements Rule
{
    private UploadTypeEnum $uploadType;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(UploadTypeEnum $uploadType = UploadTypeEnum::Image)
    {
        $this->uploadType = $uploadType;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($value) {
            $uploads = Upload::where('type', $this->uploadType);

            if (is_string($value)) {
                return $uploads->where('ref', $value)->exists();
            } elseif (is_array($value)) {
                return $uploads->whereIn('ref', $value)->exists();
            } else {
                return false;
            }
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $uploadType = $this->uploadType->value;

        return "The $uploadType does not exist.";
    }
}
