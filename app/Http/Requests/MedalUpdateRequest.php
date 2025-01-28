<?php

namespace App\Http\Requests;

use Rule;
use App\Enums\MedalTypeEnum;
use App\Rules\HasSiteAccess;
use App\Rules\MedalableValueRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;

use App\Traits\ImageValidator;
use App\Traits\FailedValidationResponseTrait;

class MedalUpdateRequest extends FormRequest
{
    use FailedValidationResponseTrait, ImageValidator;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'site' => ['bail', 'required', 'exists:sites,ref', new HasSiteAccess()],
            'name' => ['bail', 'required', 'string', 'max:255'],
            'type' => ['required', new Enum(MedalTypeEnum::class)],
            'event' => ['bail', Rule::requiredIf(! isset($this->category)), Rule::prohibitedIf(isset($this->category)), new MedalableValueRule(Event::class, $this->medal?->id)],
            'category' => ['bail',Rule::requiredIf(! isset($this->event)), Rule::prohibitedIf(isset($this->event)), new MedalableValueRule(EventCategory::class, $this->medal?->id)],
            'description' => ['sometimes', 'string', 'max:1000'],
            ...$this->imageRules()
        ];
    }

    public function messages(): array
    {
        return [
            'event.prohibited' => 'The event field is prohibited when category field is present.',
            'category.prohibited' => 'The category field is prohibited when event field is present.',
            'event.required' => 'The event field is required when category field is not present.',
            'category.required' => 'The category field is required when event field is not present.',
            ...$this->imageMessages()
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'site' => [
                'description' => 'The site ref',
                'example' => '97ad9df6-bc08-4729-b95e-3671dc6192c2'
            ],
            'name' => [
                'description' => 'The name of the medal',
                'example' => 'Gold Medal'
            ],
            'type' => [
                'description' => 'The type of the medal',
                'example' => 'default'
            ],
            'event' => [
                'description' => 'The event ref that will be associated with the medal',
                'example' => '97ad9df6-bc08-4729-b95e-3671dc6192c2'
            ],
            'category' => [
                'description' => 'The category ref that will be associated with the medal',
                'example' => '97ad9df6-bc08-4729-b95e-3671dc6192c2'
            ],
            'description' => [
                'description' => 'The description of the medal',
                'example' => 'This is a gold medal'
            ],
            ...$this->imageBodyParameters()
        ];
    }
}
