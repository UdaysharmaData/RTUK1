<?php

namespace App\Modules\Charity\Requests;

use Auth;
use Rule;
use Carbon\Carbon;
use App\Enums\RoleNameEnum;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use Illuminate\Validation\Rules\Enum;
use App\Enums\CharityMembershipTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class CharityMembershipRequest extends FormRequest
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
            'expiry_date' => 'required|date_format:Y-m|after:today',
            'type' => ['required', new Enum(CharityMembershipTypeEnum::class)],
            'use_new_membership_fee' => 'required|boolean',
            'status' => 'required|boolean',
            'account_manager' => [
                'sometimes',
                'required',
                'string',
                Rule::exists('users', 'ref')->where(function ($query) { // User must exist and must have the Account Manager role
                    $query->whereIn('users.id', function($query) {
                        $query->select('role_user.user_id')
                            ->from('role_user')
                            ->where('users.ref', request()->account_manager)
                            ->where('role_user.role_id', Role::where('name', RoleNameEnum::AccountManager)->first()->id);
                    });
                }),
            ],
            'extend_membership' => 'required_with:invoice|boolean',
            'invoice' => [
                'array:held,send_on',
                Rule::requiredIf($this->extend_membership == true),
            ],
            'invoice.held' => [
                'boolean',
                Rule::requiredIf($this->invoice == true)
            ],
            'invoice.send_on' => [
                'nullable',
                'date_format:d-m-Y',
                'after:today',
                Rule::requiredIf($this->invoice && !empty($this->invoice['held']) ? true : false)
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'invoice.required' => 'The invoice held or send_on attribute is required when extend_membership is true',
            'invoice.send_on.required' => 'The invoice.send_on attribute is required when invoice.held is true',
            'invoice.held.required' => 'The invoice.held attribute is required when invoice.send_on is not empty'
        ];
    }

    public function bodyParameters()
    {
        return [
            'expiry_date' => [
                'description' => 'The charity membership expiry date (Y-m)',
                'example' => Carbon::now()->addMonths(rand(1, 24))->format('Y-m')
            ],
            'type' => [
                'description' => 'The charity membership type. Must be one of classic, partner, premium or two_year',
                'example' => CharityMembershipTypeEnum::Premium->value
            ],
            'use_new_membership_fee' => [
                'example' => true
            ],
            'status' => [
                'example' => true
            ],
            'account_manager' => [
                'description' => 'The charity\'s account manager ref',
                'example' => User::inRandomOrder()
                    ->whereHas('roles', function ($query) {
                        $query->where('name', RoleNameEnum::AccountManager);
                    })->first()->ref
            ],
        ];
    }


}
