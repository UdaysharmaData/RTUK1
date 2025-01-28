<?php

namespace App\Modules\User\Requests;

use App\Enums\SiteUserActionEnum;
use App\Modules\User\Models\SiteUser;
use App\Modules\User\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UsersActionRequest extends FormRequest
{
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
            'users_ids' => ['required', 'array'],
            'users_ids.*' => [
                function ($attribute, $value, $fail) {
                    if (User::query()
                        ->withoutEagerLoads()
                        ->where('id', '=', $value)
                        ->exists()
                    ) {
                        SiteUser::query()
                            ->firstOrCreate(['user_id' => $value, 'site_id' => clientSiteId()]);
                    } else $fail("User with specified id: $value not found.");
                }
            ],
            'action' => ['required', new Enum(SiteUserActionEnum::class)]
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'users_ids.*.exists' => 'User not found',
            'action.Illuminate\Validation\Rules\Enum' => 'Invalid action specified'
        ];
    }
}
