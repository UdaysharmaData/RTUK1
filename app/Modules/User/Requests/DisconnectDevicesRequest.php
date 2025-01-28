<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisconnectDevicesRequest extends FormRequest
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
            'devices' => ['required', 'array', 'min:1'],
            'devices.*' => [
                'required',
                $this->isValidUserDeviceId()
            ]
        ];
    }

    /**
     * @return \Closure
     */
    private function isValidUserDeviceId(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $exists = $this->route('user')?->connectedDevices()
                ->where('id', $value)
                ->exists();
            if (! ($exists)) $fail('Invalid device specified.');
        };
    }
}
