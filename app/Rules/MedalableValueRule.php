<?php

namespace App\Rules;

use App\Modules\Setting\Enums\SiteCodeEnum;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Setting\Models\Site;
use Illuminate\Contracts\Validation\Rule;

class MedalableValueRule implements Rule
{
    /**
     *
     * @var string
     */
    private string $errorMessage;

    /**
     * medal id
     *
     * @var int|null
     */
    private ?int $id;

    private string $medalableType;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($medalableType, $id = null)
    {
        $this->medalableType = $medalableType;
        $this->id = $id;
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
        $isValid = true;
        $medalable = null;

        if ($this->medalableType == EventCategory::class && request()->filled('category')) {
            $medalable = EventCategory::filterBySite()->where('ref', $value)->exists();

            if (!$medalable) {
                $isValid = false;
                $this->errorMessage = 'The selected category is invalid';
            }
        } else if ($this->medalableType == Event::class && request()->filled('event')) {
            $medalable = Event::filterBySite()->where('ref', $value)->exists();

            if (!$medalable) {
                $isValid = false;
                $this->errorMessage = 'The selected event is invalid';
            }
        }


        return $isValid ;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->errorMessage;
    }

    /**
     * @param $medalable
     * @return bool
     */
    private function customSiteCondition($medalable): bool
    {
        if ($medalable && Site::makingRequest()->where('code', SiteCodeEnum::RunThrough->value)->exists()) {
            $exists = $medalable->medals()->when($this->id, function ($query) {
                $query->where('id', '!=', $this->id);
            })->exists();

            if ($exists) {
                $this->errorMessage = "A medal has already been assigned to the selected entity";
                return false;
            }
        }

        return true;
    }
}
