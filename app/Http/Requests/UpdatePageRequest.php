<?php

namespace App\Http\Requests;


use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

use App\Enums\PageStatus;
use App\Traits\FaqCustomValidator;
use App\Traits\MetaCustomValidator;


class UpdatePageRequest extends FormRequest
{
    use MetaCustomValidator, FaqCustomValidator;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string'],
            'url' => [
                'sometimes',
                'url',
                Rule::unique('pages')
                    ->where(fn ($query) => $query->where('site_id', clientSiteId()))
                    ->ignore($this->route('page')?->id),
            ],
            'status' => ['sometimes', new Enum(PageStatus::class)],
            // meta
            ...$this->metaRules(),
            // FAQs
            ...$this->faqUpdateRules($this->route('page')),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (isset($this->status)) {
            $this->merge([
                'status' => (bool) $this->status,
            ]);
        }
    }

    /**
     * @return void
     */
    protected function passedValidation(): void
    {
        if (isset($this->keywords)) {
            $this->replace(['keywords' => implode(',', $this->keywords)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            ...$this->metaMessages(),
            ...$this->faqMessages(),
            'url.unique' => 'URL already assigned to another page on this platform.'
        ];
    }

    /**
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Specifies page name attribute.',
                'example' => 'FAQs Page'
            ],
            'url' => [
                'description' => 'Page URL.',
                'example' => 'https://path-for-page.test'
            ],
            'status' => [
                'description' => 'Specifies whether the page is live or not.',
                'example' => '1'
            ],
            ...$this->metaBodyParameters(),
            ...$this->faqUpdateBodyParameters()
        ];
    }
}
