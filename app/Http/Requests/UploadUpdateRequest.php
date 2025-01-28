<?php

namespace App\Http\Requests;

use App\Rules\DataUriFileSize;
use Illuminate\Foundation\Http\FormRequest;
use Intervention\Validation\Rules\DataUri;

class UploadUpdateRequest extends FormRequest
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
            'media' => ['required', 'array:title,alt,caption,description,file,device_versions'],
            'media.*.title' => ['sometimes', 'nullable', 'string'],
            'media.*.alt' => ['sometimes', 'nullable', 'string'],
            'media.*.caption' => ['sometimes', 'nullable', 'string'],
            'media.*.description' => ['sometimes', 'nullable', 'string'],
            'media.*.file' => ['bail','sometimes', 'string', new DataUri(), new DataUriFileSize()],
            'media.*.device_versions' => ['sometimes', 'nullable', 'array:card,mobile,tablet,desktop'],
            'media.*.device_versions.card' => ['bail', 'sometimes', 'string', new DataUri(), new DataUriFileSize()],
            'media.*.device_versions.mobile' => ['bail', 'sometimes', 'string', new DataUri(), new DataUriFileSize()],
            'media.*.device_versions.tablet' => ['bail', 'sometimes', 'string', new DataUri(), new DataUriFileSize()],
            'media.*.device_versions.desktop' => ['bail', 'sometimes', 'string', new DataUri(), new DataUriFileSize()],
        ];
    }

    /**
     * bodyParameters
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'media' => [
                'title' => 'media',
                'description' => 'The media to be updated',
                'required' => true,
                'value' => [
                    [
                        'title' => 'title',
                        'description' => 'The title of the media',
                        'required' => false,
                        'value' => 'string',
                    ],
                    [
                        'title' => 'alt',
                        'description' => 'The alt text of the media',
                        'required' => false,
                        'value' => 'string',
                    ],
                    [
                        'title' => 'caption',
                        'description' => 'The caption of the media',
                        'required' => false,
                        'value' => 'string',
                    ],
                    [
                        'title' => 'description',
                        'description' => 'The description of the media',
                        'required' => false,
                        'value' => 'string',
                    ],
                    [
                        'title' => 'file',
                        'description' => 'The file of the media',
                        'required' => true,
                        'value' => 'string',
                    ],
                    [
                        'title' => 'device_versions',
                        'description' => 'The device versions of the media',
                        'required' => false,
                        'value' => [
                            [
                                'title' => 'card',
                                'description' => 'The card version of the media',
                                'required' => false,
                                'value' => 'string',
                            ],
                            [
                                'title' => 'mobile',
                                'description' => 'The mobile version of the media',
                                'required' => false,
                                'value' => 'string',
                            ],
                            [
                                'title' => 'tablet',
                                'description' => 'The tablet version of the media',
                                'required' => false,
                                'value' => 'string',
                            ],
                            [
                                'title' => 'desktop',
                                'description' => 'The desktop version of the media',
                                'required' => false,
                                'value' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
