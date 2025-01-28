<?php

namespace App\Modules\Event\Requests;

use Illuminate\Validation\Rule;
use App\Modules\Event\Models\Serie;
use Illuminate\Foundation\Http\FormRequest;

use App\Modules\Setting\Models\Site;
use App\Traits\DraftCustomValidator;

class SerieCreateRequest extends FormRequest
{
    use DraftCustomValidator;

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
            'name' => ['bail', 'required_with:site_id', 'string', function ($attribute, $value, $fail) { // Ensure the serie does not exists for the site making the request
                $serie = Serie::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id);

                if ($serie->exists()) {
                    return $fail('A serie with that name already exists.');
                }

                if ($serie->withTrashed()->exists()) {
                    return $fail('A [deleted] serie with that name already exists. Either restore it or delete it permanently.');
                }
            }],
            'site_id' => ['bail', 'required', 'integer', 'exists:sites,id', function ($attribute, $value, $fail) { // Check if the user has access to the site (site_id submitted)
                $hasAccess = Site::makingRequest()
                    ->hasAccess()
                    ->whereId($value)
                    ->first();

                if (!$hasAccess) {
                    $fail('Sorry! You don\'t have access to the site submitted.');
                    \Log::channel('adminanddeveloper')->debug('Attempt to access a site the user does not have access to. Site ID: ' . $value . ' User ID: ' . auth()->id());
                }
            }],
            'description' => ['nullable', 'string'],
            ...$this->draftRules()
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
                'description' => 'The description of the serie',
                'example' => 'Serie description'
            ],
            ...$this->draftBodyParameters()
        ];
    }
}
