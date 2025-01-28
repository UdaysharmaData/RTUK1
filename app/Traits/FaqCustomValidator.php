<?php

namespace App\Traits;

use Illuminate\Validation\Rule;

use App\Models\Faq;
use App\Models\FaqDetails;
use App\Contracts\CanHaveManyFaqs;
use App\Enums\UploadTypeEnum;
use App\Rules\EnsureUploadDataExists;

trait FaqCustomValidator
{

    /**
     * @return array
     */
    public function faqCreateRules(): array
    {
        return [
            'faqs' => ['sometimes', 'array'],
            'faqs.*' => ['required', 'array'],
            'faqs.*.section' => ['required', 'string'],
            'faqs.*.description' => ['sometimes', 'nullable', 'string'],
            'faqs.*.details' => ['required_with:faqs.*', 'array'],
            'faqs.*.details.*.question' => ['required', 'string'],
            'faqs.*.details.*.answer' => ['required_with:faqs.*.details.*.question', 'string'],
            'faqs.*.details.*.images' => ['sometimes', 'required', 'array'],
            'faqs.*.details.*.images.*' => [new EnsureUploadDataExists(UploadTypeEnum::Image)],
        ];
    }

    /**
     * faqUpdateRule
     *
     * @param CanHaveManyFaqs $model
     * @return array
     */
    public function faqUpdateRules(?CanHaveManyFaqs $model): array
    {
        $faqs = $model?->faqs()->pluck('id')->toArray();
        $details = FaqDetails::whereIn('faq_id', $faqs ?? [])->pluck('id')->toArray();

        return [
            'faqs' => ['sometimes', 'array'],
            'faqs.*.faq_id' => [
                'sometimes',
                'integer',
                Rule::exists('faqs', 'id')
                    ->where('faqsable_type', $model ? get_class($model) : null)
                    ->where('faqsable_id', $model?->id)
            ],
            'faqs.*.section' => ['required_without:faqs.*.faq_id', 'string'],
            'faqs.*.description' => ['sometimes', 'nullable', 'string'],
            'faqs.*.details' => ['required_with:faqs.*', 'array'],
            'faqs.*.details.*.details_id' => ['prohibited_if:faqs.*.faq_id,null', 'integer', Rule::in($details)],
            'faqs.*.details.*.question' => ['required_without:faqs.*.details.*.details_id', 'string'],
            'faqs.*.details.*.answer' => ['required_without:faqs.*.details.*.details_id', 'string'],
            'faqs.*.details.*.images' => ['sometimes', 'required', 'array'],
            'faqs.*.details.*.images.*' => ['sometimes', 'required', 'string', new EnsureUploadDataExists(UploadTypeEnum::Image)],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function faqMessages(): array
    {
        return [
            'faqs.*.faq_id' => 'The selected faq is invalid',
            'faqs.*.details.*' => 'Invalid details provided for FAQ',
            'faqs.*.details.*.details_id' => 'The selected faq detail is invalid',
            'faqs.*.details.*.details_id.prohibited_if' => 'The faq_id field is required when details_id is present.', // This message is similar to that of required_with because null was passed to the second argument of prohibited_if. So it somehow behaves like the required_with.
            'faqs.*.details.*.images.*' => 'Invalid image provided for FAQ',
            'faqs.*.details.*.question' => 'The question field is required.',
            'faqs.*.details.*.answer' => 'The answer field is required.',
        ];
    }

    /**
     * Get Faq update body parameters
     * 
     * @return array
     */
    public function faqUpdateBodyParameters(): array
    {
        return [
            'faqs.*.faq_id' => [
                'description' => 'The FAQ id',
                'example' => Faq::inRandomOrder()->value('id')
            ],
            'faqs.*.details.*.details_id' => [
                'example' => FaqDetails::inRandomOrder()->value('id')
            ],
            ...$this->faqDefaultBodyParameters()
        ];
    }

    /**
     * Get Faq create body parameters
     *
     * @return array
     */
    public function faqCreateBodyParameters(): array
    {
        return $this->faqDefaultBodyParameters();
    }

    /**
     * Get Faq default body parameters
     *
     * @return array
     */
    private function faqDefaultBodyParameters(): array
    {
        return [
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
            ],
            'faqs.*.details.*.images.*' => [
                "description" => "Image Ref",
                "example" => "97ad9df6-bc08-4729-b95e-3671dc6192c2"
            ]
        ];
    }
}
