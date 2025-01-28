<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use Illuminate\Support\Facades\DB;

class CityAllQueryParamsRequest extends FormRequest
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
            // 'region' => ['sometimes', 'nullable', 'exists:regions,ref'],
            'region' => [
                'sometimes',
                'nullable',
                function ($attribute, $value, $fail) {
                    $uuids = explode(',', $value); // Split the input into an array of UUIDs
                    foreach ($uuids as $uuid) {
                        if (!\Illuminate\Support\Str::isUuid($uuid)) {
                            return $fail("The $attribute must contain valid UUIDs.");
                        }
                        if (!DB::table('regions')->where('ref', $uuid)->exists()) {
                            return $fail("The $attribute contains an invalid UUID: $uuid.");
                        }
                    }
                },
            ],
            'country' => ['sometimes', 'nullable', 'string'],
            ...(new DefaultListingQueryParamsRequest())->rules()
        ];
    }
}
