<?php

namespace App\Modules\Charity\Requests;

use Auth;
use Rule;
use App\Enums\SocialPlatformEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class CharityUpdateContentRequest extends FormRequest
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
            'mission_values' => ['required', 'string'],
            'description' => ['required', 'string'],
            'video' => ['nullable', 'url'],
            'donation_link' => ['nullable', 'active_url'],
            'website' => ['nullable', 'active_url'],
            'socials' => [
                'nullable',
            ],
            'socials.*.platform' => [
                'distinct',
                new Enum(SocialPlatformEnum::class),
                Rule::requiredIf($this->socials == true)
            ],
            'socials.*.url' => [
                'active_url',
                'distinct',
                Rule::requiredIf($this->socials == true)
            ],
            'images' => ['sometimes', 'required', 'array'],
            'images.*' => ['base64image', 'base64mimes:jpeg,png,jpg,gif,svg,webp,avif', 'base64max:10240'],
            'meta' => ['sometimes', 'required', 'array:title,description,keywords'],
            'meta.title' => [
                'string',
                Rule::requiredIf($this->meta == true)
            ],
            'meta.description' => [
                'string',
                Rule::requiredIf($this->meta == true)
            ],
            'meta.keywords' => [
                'string',
                Rule::requiredIf($this->meta == true)
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
            'meta.title.required' => 'The meta title field is required',
            'meta.description.required' => 'The meta description field is required',
        ];
    }

    public function bodyParameters()
    {
        return [
            'mission_values' => [
                'description' => 'The charity mission values',
                'example' => 'Qui magna et do dolor cillum fugiat nisi sint ullamco eiusmod est. Sint elit dolore ea officia aute ipsum qui officia. Dolor nisi cillum reprehenderit elit eu ut commodo laboris Lorem aliquip. Excepteur exercitation elit Lorem culpa sit. Consequat consectetur cillum in aute incididunt magna dolor irure reprehenderit ex aute est veniam velit. Reprehenderit non culpa eiusmod duis ad pariatur non qui in incididunt in voluptate est nostrud.
                    Enim amet labore pariatur est dolore qui. Occaecat ipsum aliquip ipsum sit ipsum est nostrud tempor tempor. Pariatur pariatur veniam velit aute ex do laborum sint est deserunt consectetur do exercitation.'
            ],
            'description' => [
                'description' => 'The charity description',
                'example' => '<p>In dolor in ad cillum aliquip nostrud. Fugiat exercitation in pariatur commodo id. Quis ullamco nisi non reprehenderit non nisi. Sunt Lorem irure esse sunt velit ut quis elit deserunt consectetur excepteur. Duis nisi fugiat aliquip magna reprehenderit magna. Aliquip magna ad velit sit elit incididunt cupidatat ea commodo elit commodo. Dolor proident amet et duis occaecat irure nulla cillum et tempor.</p>
                    <p>Qui magna et do dolor cillum fugiat nisi sint ullamco eiusmod est. Sint elit dolore ea officia aute ipsum qui officia. Dolor nisi cillum reprehenderit elit eu ut commodo laboris Lorem aliquip. Excepteur exercitation elit Lorem culpa sit. Consequat consectetur cillum in aute incididunt magna dolor irure reprehenderit ex aute est veniam velit. Reprehenderit non culpa eiusmod duis ad pariatur non qui in incididunt in voluptate est nostrud.</p>'
            ],
            'video' => [
                'description' => 'The charity video',
                'example' => 'https://www.youtube.com/watch?v=qRidinG3PAw'
            ],
            'donation_link' => [
                'description' => 'The link to the charity donation page',
                'example' => 'http://nader.com/illum-necessitatibus-voluptas-enim-amet'
            ],
            'website' => [
                'description' => 'The charity website url',
                'example' => 'http://www.wwf.org.uk/'
            ],
            'meta.title' => [
                'description' => 'The charity meta title',
                'example' => 'The meta title'
            ],
            'meta.description' => [
                'description' => 'The charity meta description',
                'example' => 'The meta description'
            ],
        ];
    }

 
}
