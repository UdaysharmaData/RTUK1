<?php

namespace App\Http\Requests;

use App\Enums\UploadTypeEnum;
use App\Rules\DataUriFileSize;
use App\Services\FileManager\FileManager;
use App\Traits\FailedValidationResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Intervention\Validation\Rules\DataUri;

class UploadCreateRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    protected $media;

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
        $rules = [
            'media' => ['required', 'array'],
            'media.*.title' => ['sometimes', 'nullable', 'string'],
            'media.*.alt' => ['sometimes', 'nullable', 'string'],
            'media.*.caption' => ['sometimes', 'nullable', 'string'],
            'media.*.description' => ['sometimes', 'nullable', 'string'],
            'media.*.private' => ['sometimes', 'boolean']
        ];

        $this->media = request('media');

        if ($this->media) {
            foreach ($this->media as $index => $value) {
                if (isset($value['file']) && $value['file']) {
                    $rules = array_merge($rules, $this->getFileValidationRule($index));
                } else {
                    $rules['media.' . $index . '.file'] = ['required'];
                }
            }
        } else {
            $rules['media.*.file'] = ['required', new DataUri()];
            $rules['media.*.device_versions'] = ['sometimes', 'array:card,mobile,tablet,desktop'];
            $rules['media.*.device_versions.card'] = ['sometimes', 'string', new DataUri()];
            $rules['media.*.device_versions.mobile'] = ['sometimes', 'string', new DataUri()];
            $rules['media.*.device_versions.tablet'] = ['sometimes', 'string', new DataUri()];
            $rules['media.*.device_versions.desktop'] = ['sometimes', 'string', new DataUri()];
        }

        return $rules;
    }

    /**
     * getFileValidationRule
     *
     * @param  mixed $index
     * @return void
     */
    private function getFileValidationRule($index)
    {
        try {
            $fileType = FileManager::guessFileType(FileManager::createFileFromUrl($this->media[$index]['file']));
            $key = 'media.' . $index;
        } catch (\Exception $e) {
            return ['media.' . $index . '.file' => ['required', new DataUri()]];
        }

        switch ($fileType) {
            case UploadTypeEnum::Image->value:
                $media[$key . '.file'] = ['required', new DataUri(), new DataUriFileSize()];
                $media[$key . '.device_versions'] = ['bail', 'sometimes', 'array:card,mobile,tablet,desktop'];
                $media[$key . '.device_versions.card'] = ['bail', 'sometimes', 'string', new DataUri(), new DataUriFileSize()];
                $media[$key . '.device_versions.mobile'] = ['bail', 'sometimes',  'string', new DataUri(), new DataUriFileSize()];
                $media[$key . '.device_versions.tablet'] = ['bail', 'sometimes', 'string', new DataUri(), new DataUriFileSize()];
                $media[$key . '.device_versions.desktop'] = ['bail', 'sometimes',  'string', new DataUri(), new DataUriFileSize()];

                break;
            case UploadTypeEnum::Video->value:
                $media[$key . '.file'] = ['required', new DataUri(), new DataUriFileSize(2000, UploadTypeEnum::Video)];

                break;
            case UploadTypeEnum::CSV->value:
                $media[$key . '.file'] = ['required', new DataUri(), new DataUriFileSize(2000, UploadTypeEnum::CSV)];

                break;
            case UploadTypeEnum::PDF->value:
                $media[$key . '.file'] = ['required', new DataUri(), new DataUriFileSize(50000, UploadTypeEnum::PDF)];

                break;
            case UploadTypeEnum::Audio->value:
                $media[$key . '.file'] = ['required', new DataUri(), new DataUriFileSize(2000, UploadTypeEnum::Audio)];

                break;
            default:
                throw new \Exception('Invalid file type');
        }

        return $media;
    }
}
