<?php

namespace App\Services\FileManager;

use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Enums\UploadTypeEnum;
use App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 *
 */
class FileManager
{

    /**
     * map clientName to an alias
     */
    const DIRECTORIES = [
        'RunThrough' => 'runthrough',
    ];
    /**
     * form request
     * @var Request
     */
    private Request $request;
    /**
     * name of input field under evaluation
     * @var string
     */
    private string $inputField;
    /**
     * optional filename can be provided while triggering an Upload service
     * @var ?string
     */
    private ?string $fileName = null;

    /**
     * optional filesystem `disk` param can be provided while triggering an Upload service
     * @var string
     */
    private string $disk;
    /**
     * uploaded file binary
     * @var UploadedFile
     */
    private UploadedFile $file;
    /**
     * specify default validation rules for uploads
     * @var array
     */
    public array $rules = [];

    /**
     * to use this service, specify a model that implements either Uploadable types
     * overwrite default filesystem disk with optional string param
     * @param CanHaveUploadableResource|CanHaveManyUploadableResource $uploader
     */
    public function __construct(
        protected CanHaveUploadableResource | CanHaveManyUploadableResource $uploader,
    ) {
    }

    /**
     * call method on service with the params. `field` represents the name assigned to the
     * file form input field, and $fileName is optional [$request assumes $request already validated]
     * @param Request $request
     * @param string $inputField
     * @param string|null $fileName
     * @param string|null $disk
     * @return array|void
     * @throws \Exception
     */
    public function upload(
        Request $request,
        string $inputField,
        ?string $fileName = null,
        ?string $disk = null
    ): array|null {
        $this->setRules($inputField);

        if ($request->hasFile($inputField)) {
            $this->updateServiceProperties($request, $inputField, $fileName, $disk);

            $payload = $this->request->file($this->inputField);
            $uploads = [];

            if (is_array($payload)) {
                foreach ($payload as $file) {
                    $this->file = $file;
                    $uploads[] = $this->storeUploadData();
                }
            } else {
                $this->file = $payload;
                $uploads[] = $this->storeUploadData();
            }

            return $uploads;
        }
    }

    /**
     * determine uploading strategy
     * @return mixed|void
     * @throws \Exception
     */
    private function storeUploadData()
    {
        if ($this->file->isValid()) {
            if ($this->uploader instanceof CanHaveUploadableResource) {
                return $this->getUploadedResource(
                    'upload',
                    'updateOrCreate'
                );
            } elseif ($this->uploader instanceof CanHaveManyUploadableResource) {
                return $this->getUploadedResource(
                    'uploads',
                    'create'
                );
            } else throw new \Exception("Uploader class [$this->uploader] is not configured to support file upload.");
        } else throw new \Exception('An error occurred during your file upload.');
    }

    /**
     * store new model instance to database
     * @param string $modelRelationship
     * @param string $modelAction
     * @return mixed
     * @throws \Exception
     */
    private function getUploadedResource(
        string $modelRelationship,
        string $modelAction
    ): mixed {
        $path = $this->storeFileToDirectory();
        return $this->uploader
            ->$modelRelationship()
            ->$modelAction([
                'url' => $path,
                'type' => $this->getFileType(),
                'meta' => self::setFileMetadata($path)
            ]);
    }

    /**
     * store file to dir and return path
     * @return mixed
     */
    private function storeFileToDirectory(): mixed
    {
        if ($this->getFileName()) {
            return $this->file->{$this->getStoreTypeMethod()}(
                $this->getDirectoryRelativePath(),
                $this->getFileName(),
                $this->getDisk()
            );
        } else {
            return $this->file->{$this->getStoreTypeMethod()}(
                $this->getDirectoryRelativePath(),
                $this->getDisk()
            );
        }
    }

    /**
     * automated way to logically create directory to upload to on-the-fly
     * @return string
     */
    private function getDirectoryRelativePath(): string
    {
        $directory = Str::lower(class_basename($this->uploader));

        return trim("/uploads/$directory"); // todo: refactor directory name creator logic
    }

    /**
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed|string
     */
    private function getDisk(): mixed
    {
        return $this->disk ?? config('filesystems.default');
    }

    /**
     * override default disk on-the-fly
     * @param string|null $disk
     * @return void
     */
    public function setDisk(string $disk = null): void
    {
        $disk
            ? $this->disk = $disk
            : $this->disk = config('filesystems.default');
    }

    /**
     * use `storeAs` store while specifying a filename
     * or use `store` to assign auto generated filename
     * @return string
     */
    private function getStoreTypeMethod(): string
    {
        return $this->fileName ? 'storeAs' : 'store';
    }

    /**
     * fileName can be overwritten on-the-fly
     * @param string|null $fileName
     * @return $this
     */
    public function setFileName(string $fileName = null): static
    {
        $fileName
            ? $this->fileName = $fileName
            : $this->fileName = $this->request->file($this->inputField)->hashName();

        return $this;
    }

    /**
     * getter for fileName prop
     * @return string|null
     */
    private function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * automate logic for creating a custom fileName
     * @return string
     */
    private function computedFileName(): string
    {
        return $this->file->hashName();
    }

    /**
     * get the filetype from mimetype and resolve from Enum
     * @return string
     * @throws \Exception
     */
    private function getFileType(): string
    {
        $fileMimeType = $this->file->getMimeType();

        $fileTypeEnum = match ($fileMimeType) {
            'image/png', 'image/jpeg' => UploadTypeEnum::Image,
            'application/pdf' => UploadTypeEnum::PDF,
            'video/mp4', 'video/mpeg' => UploadTypeEnum::Video,
            default => throw new \Exception("Unsupported [$fileMimeType] filetype!"),
        };

        return $fileTypeEnum->value;
    }

    /**
     * @param Request $request
     * @param string $inputField
     * @param string|null $fileName
     * @param string|null $disk
     * @return void
     */
    private function updateServiceProperties(
        Request $request,
        string $inputField,
        ?string $fileName,
        ?string $disk
    ): void {
        $this->request = $request;
        $this->inputField = $inputField;
        $this->setDisk($disk);
        !(is_array($this->request->file($this->inputField)))
            && $this->setFileName($fileName);
    }

    /**
     * set rules based on specified field
     * @param string $inputField
     * @return void
     */
    private function setRules(string $inputField): void
    {
        $this->rules[$inputField] = ['required', 'file'];
    }

    /**
     * @param string $path
     * @return array
     */
    public static function setFileMetadata(string $path): array
    {
        $meta['size'] = File::size($path);
        $meta['name'] = File::name($path);

        return $meta;
    }

    /**
     * get url for a file based on path and file visibility
     * @param string $path
     * @return string
     */
    public static function getFileUrl(string $path): string
    {
        if (Storage::getVisibility($path) === 'private') {
            return Storage::temporaryUrl(
                $path,
                now()->addMinutes(5)
            );
        }
        return Storage::url($path);
    }

    /**
     * get a public url for a public file based on path
     * @param string $path
     * @return string
     */
    public static function getUrlForPublicFile(string $path): string
    {
        return Storage::url($path);
    }

    /**
     * get a temp signed url for a private file based on path
     * @param string $path
     * @param int $minutes
     * @return string
     */
    public static function getUrlForPrivateFile(string $path, int $minutes = 5): string
    {
        return Storage::temporaryUrl(
            $path,
            now()->addMinutes($minutes)
        );
    }

    /**
     * @param string $url
     * @param string $originalName
     * @param string|null $mimeType
     * @param int|null $error
     * @param bool $test
     * @return UploadedFile
     * @throws UnableToOpenFileFromUrlException
     */
    public static function createFileFromUrl(string $url, string $originalName = '', string $mimeType = null, int $error = null, bool $test = false): UploadedFile
    {
        if (!$stream = @fopen($url, 'r')) {
            throw new UnableToOpenFileFromUrlException("Unable to open file from [$url]");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'url-generated-file-');

        file_put_contents($tempFile, $stream);

        return new UploadedFile($tempFile, $originalName, $mimeType, $error, $test);
    }

    /**
     * @param string $modelAlias
     * @param bool $private
     * @return string
     */
    public static function clientBasedDirectory(string $modelAlias, bool $private): string
    {
        //        $clientDirectory = self::DIRECTORIES[clientName()];
        $visibility = $private ? 'private' : 'public';

        return "/$visibility/uploads/$modelAlias";
    }

    /**
     * @param UploadedFile|string $file
     * @return string
     * @throws \Exception
     */
    public static function guessFileType(UploadedFile|string $file): string
    {
        if ($file instanceof UploadedFile) {
            $fileMimeType = $file->getMimeType();
        } else {
            $fileMimeType = explode(';', explode(',', $file)[0])[0];
        }

        $fileTypeEnum = match ($fileMimeType) {
            'image/png', 'image/jpeg' => UploadTypeEnum::Image,
            'application/pdf' => UploadTypeEnum::PDF,
            'video/mp4', 'video/mpeg' => UploadTypeEnum::Video,
            default => throw new \Exception("Unsupported [$fileMimeType] filetype!"),
        };

        return $fileTypeEnum->value;
    }

    /**
     * @param UploadTypeEnum $type
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     * @throws \Exception
     */
    public static function getPath(UploadTypeEnum $type): mixed
    {
        return match ($type->value) {
            'image' => config('app.images_path'),
            'audio' => config('app.audios_path'),
            'csv' => config('app.csvs_path'),
            'pdf' => config('app.pdf_path'),
            default => throw new \Exception("Unknown file type [$type]"),
        };
    }

    /**
     * @param string                                   $disk
     * @param string                                   $fileVisibility
     * @param UploadTypeEnum                           $type
     * @param array|\Illuminate\Http\UploadedFile|null $file
     * @return false|string
     * @throws \Exception
     */
    public static function uploadToDisk(string $disk, string $fileVisibility, UploadTypeEnum $type, array|\Illuminate\Http\UploadedFile|null $file): string|false
    {
        return Storage::disk($disk)->putFile(
            self::getPath($type),
            $file,
            $fileVisibility
        );
    }

    public static function deleteFile($filePath, string $disk = null)
    {
        $disk = $disk ?: config('filesystems.default');

        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
        }
    }
}
