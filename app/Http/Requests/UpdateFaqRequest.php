<?php

namespace App\Http\Requests;

use App\Models\Faq;
use App\Models\FaqDetails;
use Intervention\Validation\Rules\DataUri;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class UpdateFaqRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
            'faqs' => ['required', 'array'],
            'faqs.*' => ['required', 'array'],
            'faqs.*.faq_id' => ['required', 'integer', 'exists:faqs,id'],
            'faqs.*.section' => ['required', 'string'],
            'faqs.*.description' => ['sometimes', 'nullable', 'string'],
            'faqs.*.details' => ['sometimes', 'required', 'array'],
            'faqs.*.details.*.details_id' => ['sometimes', 'integer', 'exists:faq_details,id'],
            'faqs.*.details.*.question' => ['string'],
            'faqs.*.details.*.answer' => ['string'],
            'faqs.*.details.*.images' => ['sometimes', 'required', 'array'],
            'faqs.*.details.*.images.*' => [new DataUri()],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'faqs.*.details.*' => 'Invalid details provided for FAQ',
            'faqs.*.details.*.images.*' => 'Invalid image provided for FAQ',
        ];
    }

    public function bodyParameters()
    {
        return [
            'faqs.*.faq_id' => [
                'description' => 'The FAQ id',
                'example' => Faq::inRandomOrder()->value('id')
            ],
            'faqs.*.section' => [
                'description' => 'The FAQ section',
                'example' => 'Mollit tempor eiusmod dolor amet laboris ad officia cillum aute ut consequat.'
            ],
            'faqs.*.description' => [
                'description' => 'The FAQ description',
                'example' => 'Ad magna dolor eiusmod sint nostrud quis laborum labore sit tempor. Irure irure esse ea eu amet duis enim. Eiusmod esse anim eiusmod exercitation ex. Fugiat sint adipisicing dolore culpa. Et eiusmod et sit aliquip qui. Consectetur deserunt sunt proident exercitation cillum fugiat cillum elit veniam eiusmod qui anim.

                Tempor et incididunt laborum excepteur ad aliquip veniam. Ex qui deserunt occaecat non in tempor adipisicing mollit voluptate. Tempor ex eiusmod elit Lorem sint ea. Officia dolore minim Lorem reprehenderit eiusmod ut tempor ex Lorem in enim ex exercitation.

                Sunt minim eiusmod excepteur in aute velit aute cupidatat culpa nisi laborum fugiat mollit. Enim consequat do nisi do consequat occaecat cillum. Qui ea et quis amet minim commodo nostrud.

                Dolore aliquip incididunt labore ipsum excepteur sint adipisicing aute ea mollit. Eu commodo irure reprehenderit ipsum laboris excepteur consectetur et pariatur. Aliquip ipsum ex occaecat exercitation ut mollit nisi. Irure est nisi consectetur aliquip adipisicing exercitation anim aliqua. Pariatur sint labore non aliquip aliqua fugiat amet esse nisi dolor ad ullamco. Excepteur aute aliquip eiusmod commodo incididunt commodo aliqua aute velit id proident adipisicing est.

                Aliqua mollit nisi officia laborum irure sint cillum nulla aliquip velit non tempor officia. Elit veniam cillum consectetur fugiat pariatur exercitation deserunt voluptate officia minim excepteur quis elit ullamco. Enim adipisicing excepteur deserunt eiusmod reprehenderit consequat fugiat exercitation. Dolor sint laborum consequat occaecat adipisicing aliqua enim ad nulla. Cillum exercitation cillum adipisicing ullamco. Anim occaecat officia voluptate enim dolor in consectetur consectetur consectetur Lorem sit id. Sunt eu excepteur ad eu esse.

                Id id exercitation reprehenderit voluptate tempor. Excepteur tempor ipsum dolore nulla in ea deserunt nostrud Lorem amet elit ad. Officia id laboris sit enim enim duis cillum veniam non irure commodo veniam duis. Nulla culpa nulla ad laborum. Reprehenderit dolor eiusmod reprehenderit adipisicing laboris Lorem dolore. In eu quis ipsum labore sit irure.'
            ],
            'faqs.*.details.*.details_id' => [
                'example' => FaqDetails::inRandomOrder()->value('id')
            ],
            'faqs.*.details.*.question' => [
                'example' => 'Mollit tempor eiusmod dolor amet laboris ad officia cillum aute ut consequat.'
            ],
            'faqs.*.details.*.answer' => [
                'example' => 'Ad magna dolor eiusmod sint nostrud quis laborum labore sit tempor. Irure irure esse ea eu amet duis enim. Eiusmod esse anim eiusmod exercitation ex. Fugiat sint adipisicing dolore culpa. Et eiusmod et sit aliquip qui. Consectetur deserunt sunt proident exercitation cillum fugiat cillum elit veniam eiusmod qui anim.

                Tempor et incididunt laborum excepteur ad aliquip veniam. Ex qui deserunt occaecat non in tempor adipisicing mollit voluptate. Tempor ex eiusmod elit Lorem sint ea. Officia dolore minim Lorem reprehenderit eiusmod ut tempor ex Lorem in enim ex exercitation.

                Sunt minim eiusmod excepteur in aute velit aute cupidatat culpa nisi laborum fugiat mollit. Enim consequat do nisi do consequat occaecat cillum. Qui ea et quis amet minim commodo nostrud.

                Dolore aliquip incididunt labore ipsum excepteur sint adipisicing aute ea mollit. Eu commodo irure reprehenderit ipsum laboris excepteur consectetur et pariatur. Aliquip ipsum ex occaecat exercitation ut mollit nisi. Irure est nisi consectetur aliquip adipisicing exercitation anim aliqua. Pariatur sint labore non aliquip aliqua fugiat amet esse nisi dolor ad ullamco. Excepteur aute aliquip eiusmod commodo incididunt commodo aliqua aute velit id proident adipisicing est.

                Aliqua mollit nisi officia laborum irure sint cillum nulla aliquip velit non tempor officia. Elit veniam cillum consectetur fugiat pariatur exercitation deserunt voluptate officia minim excepteur quis elit ullamco. Enim adipisicing excepteur deserunt eiusmod reprehenderit consequat fugiat exercitation. Dolor sint laborum consequat occaecat adipisicing aliqua enim ad nulla. Cillum exercitation cillum adipisicing ullamco. Anim occaecat officia voluptate enim dolor in consectetur consectetur consectetur Lorem sit id. Sunt eu excepteur ad eu esse.

                Id id exercitation reprehenderit voluptate tempor. Excepteur tempor ipsum dolore nulla in ea deserunt nostrud Lorem amet elit ad. Officia id laboris sit enim enim duis cillum veniam non irure commodo veniam duis. Nulla culpa nulla ad laborum. Reprehenderit dolor eiusmod reprehenderit adipisicing laboris Lorem dolore. In eu quis ipsum labore sit irure.'
            ]
        ];
    }
}
