<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use Illuminate\Support\Facades\DB;

class VenueAllQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
            'term' => ['sometimes', 'nullable', 'string'],
            'city' => ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) {
                $refs = explode(',', $value);
                foreach ($refs as $ref) {
                    if (!DB::table('cities')->where('ref', trim($ref))->exists()) {
                        $fail("The {$attribute} value '{$ref}' is invalid.");
                    }
                }
            }],
            'region' => ['sometimes', 'nullable', 'string', function ($attribute, $value, $fail) {
                $refs = explode(',', $value);
                foreach ($refs as $ref) {
                    if (!DB::table('regions')->where('ref', trim($ref))->exists()) {
                        $fail("The {$attribute} value '{$ref}' is invalid.");
                    }
                }
            }],
            'country' => ['sometimes', 'nullable', 'string'],
            ...(new DefaultListingQueryParamsRequest())->rules(),
        ];
        // return [
        //     'term' => ['sometimes', 'nullable', 'string'],
        //     'city' => ['sometimes', 'nullable', 'exists:cities,ref'],
        //     'region' => ['sometimes', 'nullable', 'exists:regions,ref'],
        //     'country' => ['sometimes', 'nullable', 'string'],
        //     ...(new DefaultListingQueryParamsRequest())->rules()
        // ];
    }
}
