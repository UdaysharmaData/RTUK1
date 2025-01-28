<?php

namespace App\Rules;

use App\Enums\UploadTypeEnum;
use App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException;
use App\Services\FileManager\FileManager;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class DataUriFileSize implements Rule
{
    private string $mb;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(
        protected int $maxSizeInKilobytes = 2000,
        protected UploadTypeEnum $type = UploadTypeEnum::Image
    ) {
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
        try {
            $bytes = FileManager::createFileFromUrl($value)->getSize();

            if ($bytes > ($this->maxSizeInKilobytes * 1000)) {
                $this->mb = round($bytes / 1000 / 1000, 1) . ' MB';

                return false;
            }

            return true;
        } catch (UnableToOpenFileFromUrlException | \Exception $exception) {
            Log::error($exception);

            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        $size = $this->getFormattedMbFromKb($this->maxSizeInKilobytes);
        $type = ucfirst($this->type->name);

        return "$type size ($this->mb) too large. Please upload another {$this->type->name} not larger than $size";
    }

    /**
     * @param $sizeInKb
     * @return string
     */
    protected function getFormattedMbFromKb($sizeInKb): string
    {
        return round($sizeInKb / 1000, 1) . ' MB';
    }
}
