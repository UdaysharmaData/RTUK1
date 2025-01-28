<?php

namespace App\Modules\Charity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class CharitySignupRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /*
    | Used to validate charity signups create and update requests
    */

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return Auth::check();
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:charity_signups,name,'.$this->id ?? ''],
            'number' => ['required', 'string', 'integer'],
            'sector' => ['required', 'string', 'max:255'],
            'website' => ['required', 'active_url'],
            'address_1' => ['required', 'string', 'max:255'],
            'address_2' => ['string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'string', 'email'],
            'contact_phone' => ['required', 'phone:AUTO,GB'],
            'terms_conditions' => ['accepted'],
            'privacy_policy' => ['accepted'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'name.unique' => 'A charity enquiry with that name already exists.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The charity name',
                'example' => 'Lindsey Lodge Hospice and Healthcare'
            ],
            'number' => [
                'description' => 'The registered charity number',
                'example' => '3837340'
            ],
            'sector' => [
                'description' => 'The charity category (name)',
                'example' => 'Cancer - General'
            ],
            'website' => [
                'description' => 'The charity website',
                'example' => 'https://www.againstbreastcancer.org.uk/'
            ],
            'address_1' => [
                'example' => 'Sir John Mills House, 12 Whitehorse Mews'
            ],
            'address_2' => [
                'example' => '37 Westminster Bridge Road'
            ],
            'city' => [
                'example' => 'London'
            ],
            'postcode' => [
                'example' => 'SE1 7QD'
            ],
            'contact_name' => [
                'example' => 'Paul Kelleman'
            ],
            'contact_phone' => [
                'example' => '+447743780217'
            ],
            'terms_conditions' => [
                'example' => 1
            ],
            'privacy_policy' => [
                'example' => 1
            ],
        ];
    }

 
}
