<?php

namespace App\Modules\Event\Requests;

use Arr;
use Rule;
use App\Traits\Response;
use App\Modules\Setting\Enums\SiteEnum;
use App\Traits\SiteTrait;
use App\Enums\GenderEnum;
use App\Modules\Setting\Enums\OrganisationEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Modules\Event\Models\EventCategoryEventThirdParty;


class CheckoutOnLDTRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait, Response;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $ecetps = [ // Collect data and validate these fields for unauthenticated users
            // Validate event
            'ecetps' => ['required'],
            'ecetps.*.ref' => [
                'required',
                'string',
                Rule::exists('event_category_event_third_party', 'ref')
            ],
            'ecetps.*.quantity' => [
                'required',
                'integer',
                'min:1',
                // 'numeric',
            ]
        ];

        $user = [   // Validate user
            'email' => ['required', 'string', 'email'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'phone' => ['required', 'phone:AUTO,GB'],
            'postcode' => ['required', 'string'],
            'gender' => ['required', new Enum(GenderEnum::class)]
        ];

        // if (auth('api')->check()) { // No user validation is applied for authenticated users as no user data is supposed to be collected
        //     return $ecetps;
        // }

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive) || !app()->environment('production')) { // Only the email (user data) is collected for the RunThrough site.
            return array_merge($ecetps, [
                'email' => ['required', 'string', 'email'],
            ]);
        }

        return array_merge($ecetps, $user);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'ecetps.required' => 'The event is not properly configured.',
            'ecetps.*.ref.required' => [
                'value' => ':input',
                'message' => 'The event is not properly configured - required.'
            ],
            // 'ecetps.*.ref.required' => 'The event is not properly configured.',
            'ecetps.*.ref.string' => [
                'value' => ':input',
                'message' => 'The event is not properly configured - string.'
            ],
            // 'ecetps.*.ref.string' => 'The event is not properly configured.',
            'ecetps.*.ref.exists' => [
                'value' => ':input',
                'message' => 'The event is not properly configured - exists.'
            ],
            // 'ecetps.*.ref.exists' => 'The event is not properly configured.',
            'ecetps.*.quantity.required' => 'The quantity is required.',
            'ecetps.*.quantity.integer' => 'The quantity must be an integer.',
            'ecetps.*.quantity.min' => 'The quantity must be at least 1.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'ecetps.*.ref' => [
                'description' => '(required)',
                'example' => EventCategoryEventThirdParty::inRandomOrder()->value('ref')
            ],
            'ecetps.*.quantity' => [
                'example' => random_int(1, 10)
            ],
            'email' => [
                'description' => 'The participant\'s email address. Required with user is not authenticated. Only the email property is required for the RunThrough site.',
                'example' => 'jameswhite@gmail.com'
            ],
            'first_name' => [
                'description' => 'The participant\'s first name. Required with user is not authenticated.',
                'example' => 'James'
            ],
            'last_name' => [
                'description' => 'The participant\'s last name. Required with user is not authenticated.',
                'example' => 'White'
            ],
            'gender' => [
                'description' => "Must be one of ".implode(', ', array_column(GenderEnum::cases(), 'value')) . ". Required with user is not authenticated.",
                'example' => GenderEnum::Male->value
            ],
            'phone' => [
                'description' => "Required with user is not authenticated.",
                'example' => '+447849675382'
            ]
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (auth('api')->check()) {
            $user = auth('api')->user()->load('profile');

            $this->merge([ // Set the enquiry data for authenticated users
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'postcode' => $user->profile?->postcode,
                'gender' => $user->profile?->gender?->value
            ]);
        }

        // Format data collected for unauthenticated users

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive) || !app()->environment('production')) { // Only the email is collected for the RunThrough site
            return [
                'email' => trim($this->email)
            ];
        }

        return [
            'first_name' => ucwords(trim($this->first_name)),
            'last_name' => ucwords(trim($this->last_name)),
            'email' => trim($this->email)
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator    $validator
     * @return JsonResponse
     *
     * @throws HttpResponseException
     */
    public function failedValidation(Validator $validator): JsonResponse
    {
        if (str_contains(implode(',', array_keys($validator->errors()->toArray())), 'ecetps')) {
            $message = "Unable to checkout at the moment! Please check the warnings on the cart!";
        } else {
            $message = "Unable to checkout at the moment! Please resolve the warnings!";
        }

        throw new HttpResponseException(
            $this->error($message, 422, Arr::undot($validator->errors()->messages()))
        );
    }
}
