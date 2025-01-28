<?php

namespace App\Modules\Partner\Requests;

use Str;
use Auth;
use Rule;
use App\Enums\SocialPlatformEnum;
use App\Modules\Setting\Models\Site;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

use App\Traits\SiteTrait;
use App\Traits\ImageValidator;
use App\Traits\FailedValidationResponseTrait;
use App\Traits\MetaCustomValidator;

class PartnerCreateRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait, ImageValidator, MetaCustomValidator;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['bail', 'required_with:site', 'string', 'regex:/^[a-zA-Z0-9\s]+$/', Rule::unique("partners")->where( // Ensure the name is unique for every site
                function ($query) {
                    return $query->where(
                        [
                            ["name", "=", $this->name],
                            ["site_id", "=", Site::where('ref', $this->site)->value('id')]
                        ]
                    );
                })],
            'site' => ['bail', 'required', 'string', 'exists:sites,ref', function ($attribute, $value, $fail) { // Check if the user has access to the site (site submitted)
                $hasAccess = Site::makingRequest()
                    ->hasAccess()
                    ->where('ref', $value)
                    ->first();

                if (!$hasAccess) {
                    $fail('Sorry! You don\'t have access to the site submitted.');
                    \Log::channel('adminanddeveloper')->debug('Attempt to access a site the user does not have access to. Site ID: ' . $value . ' User ID: ' . auth()->id());
                }
            }],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'active_url'],
            'code' => ['bail', 'nullable', 'string', Rule::unique("partners")->where( // Ensure the code is unique for every site
                function ($query) {
                    return $query->where(
                        [
                            ["code", "=", $this->code],
                            ["site_id", "=", Site::where('ref', $this->site)->value('id')]
                        ]
                    );
                })],
            'socials' => [
                'nullable',
            ],
            'socials.*.platform' => [
                'distinct',
                new Enum(SocialPlatformEnum::class),
                Rule::requiredIf($this->socials == true)
            ],
            'socials.*.url' => [
                'active_url',
                'distinct',
                Rule::requiredIf($this->socials == true)
            ],
            ...$this->metaRules(),
            ...$this->imageRules()
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'socials.*.platform.enum' => 'The selected platform is invalid.',
            'socials.*.platform.required' => 'The platform field is required.',
            'socials.*.platform.distinct' => 'The platform field has a duplicate value.',
            'socials.*.url.required' => 'The url field is required.',
            'socials.*.url.distinct' => 'The url field has a duplicate value.',
            'socials.*.url.active_url' => 'The url is not a valid URL.',
            'website.active_url' => 'The url is not a valid URL.',
            ...$this->metaMessages(),
            ...$this->imageMessages()
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the partner (required)',
                'example' => 'Lets Do This',
            ],
            'description' => [
                'description' => 'The partner description',
                'example' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
            ],
            'website' => [
                'description' => 'The website',
                'example' => 'https://spartanrace.uk/en'
            ],
            'code' => [
                'description' => 'The partner code',
                'example' => 'LDT'
            ],
            'site' => [
                'description' => 'The refs of the site. Must be one of '.implode(', ', Site::limit(5)->pluck('ref')->toArray()),
                'example' => Site::inRandomOrder()
                    ->first()?->ref
            ],
            'socials.*.platform' => [
                'example' => 'twitter'
            ],
            'socials.*.url' => [
                'example' => 'https://twitter.com/bathhalf'
            ],
            ...$this->metaBodyParameters(),
            ...$this->imageBodyParameters()
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $data = [];

        $data['name'] = trim($this->name);

        if ($this->code) {
            $data['code'] = Str::slug(Str::lower($this->code));
        }

        $this->merge($data);
    }
}
