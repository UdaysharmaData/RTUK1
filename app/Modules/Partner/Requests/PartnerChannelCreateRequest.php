<?php

namespace App\Modules\Partner\Requests;

use Str;
use Auth;
use Rule;
use App\Traits\SiteTrait;
use App\Http\Helpers\AccountType;
 
use App\Modules\Partner\Models\Partner;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PredefinedPartnerChannelEnum;
use App\Traits\FailedValidationResponseTrait;

class PartnerChannelCreateRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait;

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
            'partner' => ['bail', 'required', 'string', Rule::exists('partners', 'ref')->where(
                function ($query) {
                    if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                        $query->where('site_id', static::getSite()?->id);
                    }
                })],
            'name' => ['bail', 'required_with:partner', 'string', Rule::unique("partner_channels")->where( // Ensure the channel name is unique for every partner
                function ($query) {
                    return $query->where(
                        [
                            ["name", "=", $this->name],
                            ["partner_id", "=", Partner::where('ref', $this->partner)->value('id')]
                        ]
                    );
                })],
            'code' => ['bail', 'required', 'string', function ($attribute, $value, $fail) { // Check if the partner's code is prepended to the channel's code
                $partner = Partner::where('ref', $this->partner)
                    ->whereHas('site', function ($query) {
                        if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                            $query->where('site_id', static::getSite()?->id);
                        }
                    })->first();

                if ($partner) {
                    $codeIsPrepended = Str::contains($value, $partner->code.'-', true);

                    if (! $codeIsPrepended) {
                        $fail("Sorry! The partner's code is not prepended to the channel's code.");
                        // TODO: LOG a message to notify the developer's on slack
                    }
                }
            }]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [

        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The partner channel name',
                'example' => PredefinedPartnerChannelEnum::Bespoke->name
            ],
            'code' => [
                'description' => 'The partner channel code',
                'example' => PredefinedPartnerChannelEnum::Bespoke->value
            ],
            'partner' => [
                'description' => 'The refs of the partner. Must be one of '.implode(', ', Partner::where('site_id', static::getSite()?->id)->limit(5)->pluck('ref')->toArray()),
                'example' => Partner::inRandomOrder()
                    ->where('site_id', static::getSite()?->id)
                    ->first()?->ref
            ]
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'name' => trim($this->name),
            'code' => Str::slug(Str::lower($this->code))
        ]);
    }
}
