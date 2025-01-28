<?php

namespace App\Modules\Charity\Requests;

use Auth;
use App\Modules\Charity\Models\Charity;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class CharityUpdateProfileRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
            'name' => ['required', 'unique:charities,name,'.$this->id],
            'email' => ['required', 'email', 'unique:users,email,'.Charity::find($this->id)?->charityOwner?->user?->id],
            'category' => ['required', 'string', 'exists:charity_categories,slug'],
            'support_email' => ['required', 'email'],
            'address' => ['required', 'string', 'max:150'],
            'postcode' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string'],
            'country' => ['required', 'string'],
            'phone' => ['required', 'phone:AUTO,GB'],
            'finance_contact_name' => ['nullable', 'string'],
            'finance_contact_email' => ['nullable', 'email'],
            'finance_contact_phone' => ['nullable', 'phone:AUTO,GB'],
            'show_in_external_feeds' => ['sometimes', 'required', 'boolean'],
            'show_in_vmm_external_feeds' => ['sometimes', 'required', 'boolean'],
            'external_strapline' => ['sometimes', 'required', 'boolean'],
            'password' => [
                'sometimes',
                'required',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.unique' => 'A charity with that name already exists.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The charity name',
                'example' => 'WWF',
            ],
            'email' => [
                'description' => 'The charity owner email address',
                'example' => 'teampanda@wwf.org.uk',
            ],
            'category' => [
                'description' => 'The charity category (slug)',
                'example' => 'environment-conservation'
            ],
            'support_email' => [
                'description' => 'The charity email address (support email)',
                'example' => 'johndoe237@gmail.com',
            ],
            'address' => [
                'description' => 'The charity address',
                'example' => 'Fourth Floor, Maya House, 134-138 Borough High St'
            ],
            'city' => [
                'example' => 'Swansea'
            ],
            'country' => [
                'example' => 'United Kingdom'
            ],
            'postcode' => [
                'example' => 'SE1 1LB'
            ],
            'phone' => [
                'example' => '+447815176034'
            ],
            'password' => [
                'example' => 'Sts@2022'
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'name' => trim($this->name),
            'email' => trim($this->email),
            'password' => trim($this->password),
            'support_email' => trim($this->support_email),
            'finance_contact_email' => trim($this->finance_contact_email),
        ]);
    }
}
