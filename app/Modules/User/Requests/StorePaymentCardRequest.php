<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Intervention\Validation\Rules\Creditcard;

class StorePaymentCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return request()->user()->id === $this->route('user')?->id
            || request()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'card_name' => ['required', 'string'],
            'card_number' => [
                new Creditcard(),
                'required',
                'string',
                'digits_between:16,16',
                Rule::unique('payment_cards')
                    ->where(fn ($query) => $query->where('user_id', $this->route('user')?->id))
            ],
            'expiry_date' => ['required', 'date', 'date_format:d/m/Y', 'after:today']
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'card_number.digits_between' => 'The card number must be exactly 16 digits.',
            'card_number.unique' => 'You already added a card with this number.',
            'expiry_date.after' => 'This card is expired.'
        ];
    }
}
