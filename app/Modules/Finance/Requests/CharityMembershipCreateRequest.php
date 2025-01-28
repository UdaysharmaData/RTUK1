<?php

namespace App\Modules\Finance\Requests;

use Auth;
use Rule;
use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\GenderEnum;
use App\Traits\SiteTrait;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class CharityMembershipCreateRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait;

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
        return [];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [];
    }

    public function bodyParameters()
    {
        return [

        ];
    }
}
