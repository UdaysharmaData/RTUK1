<?php

namespace App\Modules\Event\Requests;

use App\Modules\Event\Models\Sponsor;
use App\Modules\Setting\Models\Site;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SponsorUpdateRequest extends FormRequest
{
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'site_id' => ['required_with:name', 'integer', 'exists:sites,id'],
            'name' => ['sometimes', 'required_with:site_id', 'string', function ($attribute, $value, $fail) { // Ensure the sponsor does not exists for the site making the request
                $sponsor = Sponsor::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id)
                    ->where('id', '!=', $this->route('sponsor')?->id);

                if ($sponsor->exists()) {
                    return $fail('A sponsor with that name already exists.');
                }

                if ($sponsor->withTrashed()->exists()) {
                    return $fail('A [deleted] sponsor with that name already exists. Either restore it or delete it permanently.');
                }
            }],
            'description' => ['nullable', 'string'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the sponor.',
                'example' => 'Sponsor name'
            ],
            'site_id' => [
                'description' => 'The site id.',
                'example' => '1'
            ],
            'description' => [
                'description' => 'The description of the sponsor.',
                'example' => 'Sponsor description'
            ]
        ];
    }
}
