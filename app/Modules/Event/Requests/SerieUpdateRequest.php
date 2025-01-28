<?php

namespace App\Modules\Event\Requests;

use App\Modules\Event\Models\Serie;
use App\Modules\Setting\Models\Site;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SerieUpdateRequest extends FormRequest
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
            'name' => ['sometimes', 'required_with:site_id', 'string', function ($attribute, $value, $fail) { // Ensure the serie does not exists for the site making the request
                $serie = Serie::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id)
                    ->where('id', '!=', $this->route('serie')?->id);

                if ($serie->exists()) {
                    return $fail('A serie with that name already exists.');
                }

                if ($serie->withTrashed()->exists()) {
                    return $fail('A [deleted] serie with that name already exists. Either restore it or delete it permanently.');
                }
            }],
            'description' => ['nullable', 'string'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the serie.',
                'example' => 'Run'
            ],
            'site_id' => [
                'description' => 'The site id.',
                'example' => '1'
            ],
            'description' => [
                'description' => 'The description of the serie.',
                'example' => 'Serie description'
            ]
        ];
    }
}
