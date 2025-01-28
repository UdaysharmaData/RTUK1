<?php

namespace App\Modules\Event\Requests;

use App\Modules\Event\Models\Sponsor;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

use App\Modules\Setting\Models\Site;
use App\Traits\DraftCustomValidator;

class SponsorCreateRequest extends FormRequest
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
            'name' => ['bail', 'required_with:site_id', 'string', function ($attribute, $value, $fail) { // Ensure the sponsor does not exists for the site making the request
                $sponsor = Sponsor::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id);

                if ($sponsor->exists()) {
                    return $fail('A sponsor with that name already exists.');
                }

                if ($sponsor->withTrashed()->exists()) {
                    return $fail('A [deleted] sponsor with that name already exists. Either restore it or delete it permanently.');
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
                'description' => 'The name of the sponsor.',
                'example' => 'sponsor name'
            ],
            'site_id' => [
                'description' => 'The site id.',
                'example' => '1'
            ],
            'description' => [
                'description' => 'The description of the sponsor.',
                'example' => 'Sponsor description'
            ],
            ...$this->draftBodyParameters()
        ];
    }
}
