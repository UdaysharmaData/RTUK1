<?php

namespace App\Models;

use File;
use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\UploadImageSizeVariantEnum;
use App\Traits\SiteTrait;
use Bkwld\Cloner\Cloneable;
use Illuminate\Support\Str;
use App\Enums\UploadTypeEnum;
use App\Traits\BelongsToSite;
use App\Enums\UploadUseAsEnum;
use App\Traits\AddUuidRefAttribute;
use App\Modules\Setting\Models\Site;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\SiteIdAttributeGenerator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\DataCaching\Traits\CacheQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Services\FileManager\FileManager;
use App\Traits\FilterableListQueryScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, SiteTrait, UuidRouteKeyNameTrait, AddUuidRefAttribute, SiteIdAttributeGenerator, BelongsToSite, Cloneable, FilterableListQueryScope, CacheQueryBuilder;
    /**
     * @var string[]
     */
    protected $fillable = [
        'site_id',
        'url',
        'caption',
        'alt',
        'title',
        'description',
        'metadata',
        'type',
        'resized',
        'private'
    ];

    protected $casts = [
        'type' => UploadTypeEnum::class,
        'metadata' => 'json'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'storage_url',
        'name',
        'size',
        'device_versions'
    ];

    // protected $cloneable_file_attributes = ['url'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            //    $model->type = $model->getFileType(); // Not used for now. The current implementation does not send the uploaded file to an instance of the upload for its file type to be checked for. We could adopt this logic later.
            //            $model->metadata = $model; // Todo: encode metadata from file being uploaded
        });

        static::deleting(function ($model) {
            if ($model->storage_url) {
                if ($model->type == UploadTypeEnum::Image) {
                    $relativeDirectory = dirname($model->url);

                    if ($relativeDirectory != FileManager::getPath($model->type) && str_contains($relativeDirectory, pathinfo($model->name, PATHINFO_FILENAME))) {
                        Storage::disk(config('filesystems.default'))->deleteDirectory($relativeDirectory);
                    } else {
                        Storage::disk(config('filesystems.default'))->delete($model->url);
                    }
                } else {
                    Storage::disk(config('filesystems.default'))->delete($model->url);
                }
            }
        });
    }

    /**
     * Actions to perform when clonning this model.
     *
     * @return void
     */
    public function onCloning($src, $child = null)
    {
        if (Storage::disk(config('filesystems.default'))->exists($this->url)) { // Make a copy of the file on disk and change the file name.
            $extension = File::extension($this->url); // Get the file extension
            $fileName = Str::random(40) . '.' . $extension;

            if ($extension) { // Only make a copy of the file on disk if we have the path and extension.
                $path = pathinfo($this->url);
                $url = $path['dirname'] . '/' . $fileName;
                $toPath = 'public' . '/' . $url;

                Storage::copy($this->url, $toPath);

                $this->url = $url;
            }
        }
    }

    /**
     * Get the charity's logo.
     * TODO: Confirm this charity logo customization based on the website from @Fru and improve on this logic.
     *
     * @return Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->use_as && $this->use_as == UploadUseAsEnum::Logo->value) {
                    if ($value == config('app.images_path') . "LeUIUJ13eSw2i6Eu.jpg") {
                        $domain = static::getSite()?->domain;

                        if ($domain) {
                            switch (SiteEnum::from($domain)) {
                                case SiteEnum::SportForCharity:
                                    return config('app.images_path') . "Sail4C-logo.jpg";
                                    break;
                                case SiteEnum::RunForCharity:
                                    return config('app.images_path') . "RUN_4_CANCER.jpg";
                                    break;
                                case SiteEnum::CycleForCharity:
                                    return config('app.images_path') . "main_logo_Bike4Cancer.png";
                                    break;
                            }
                        }
                    }
                }

                return $value;
            }
        );
    }

    /**
     * Get the file's name.
     *
     * @return Attribute
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->storage_url) {
                    return Str::substr($this->url, strrpos($this->url, '/') + 1);
                }

                return null;
            }
        );
    }

    /**
     * Get the image's size.
     *
     * @return Attribute
     */
    protected function size(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->storage_url) {
                    return $this->formatfileConvertsize(Storage::disk(config('filesystems.default'))->size($this->url));
                }

                return null;
            }
        );
    }

    /**
     * Convert file size from bytes to KB, MB, GB, ...
     *
     * @param  float $bytes
     * @param  int   $decimals
     *
     * @return string
     */
    function formatfileConvertsize($bytes, $decimals = 2): string
    {
        $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $data = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $data)) . @$size[$data];
    }

    /**
     * Get the uploadable.
     * 
     * @return HasMany
     */
    public function uploadables(): HasMany
    {
        return $this->hasMany(Uploadable::class);
    }

    /**
     * Get the site.
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return Attribute
     */
    public function storageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk(config('filesystems.default'))->exists($this->reformatUrl())
                ? self::resolveResourceUrl($this->url)
                : null
        );
    }

    /**
     * Get the file type
     * @return UploadTypeEnum
     */
    public function getFileType(): UploadTypeEnum
    {
        // Write some logic to get the file type saving.
        // Get the file MIME type and use a switch to return the UploadTypeEnum corresponding to the type.

        return UploadTypeEnum::Image;
    }

    /**
     * Get differents versions of the image for different devices.
     *
     * @return Attribute
     */
    public function deviceVersions(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->resized) {
                    $deviceVersions = [];
                    $sizes = request('image_versions');

                    if ($sizes && is_array($sizes)) {
                        foreach ($sizes as $size) {
                            if (UploadImageSizeVariantEnum::tryFrom($size)) {
                                $path = str_replace('.', "_$size.", $this->url);
                                
                                if (Storage::disk(config('filesystems.default'))->exists($path)) {
                                    $deviceVersions[$size] = static::resolveResourceUrl($path);
                                }
                            }

                        }
                    } else {
                        foreach (UploadImageSizeVariantEnum::options() as $size) {
                            $path = str_replace('.', "_$size.", $this->url);

                            if (Storage::disk(config('filesystems.default'))->exists($path)) {
                                $deviceVersions[$size] = static::resolveResourceUrl($path);
                            }
                        };
                    }

                    return $deviceVersions;
                } else {
                    return null;
                }
            }
        );
    }

    /**
     *
     * @param  mixed $query
     * @param  mixed $useAs
     */
    public function scopeUseAs($query, UploadUseAsEnum $useAs)
    {
        return $query->whereHas('uploadables', function ($q) use ($useAs) {
            $q->where('use_as', $useAs->value);
        });
    }

    //    /**
    //     * @param $url
    //     * @param $uploadableId
    //     * @return Attribute
    //     */
    //    public function saveUpload($url, $uploadableId): Attribute
    //    {
    //        $this->updateOrCreate(['uploadable_id' => $uploadableId], [
    //            'url' => $url,
    //        ]);
    //
    //        return $this->refresh()->storageUrl();
    //    }

    /**
     * @return string
     */
    private function reformatUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string   $path
     * @param string   $realPath
     * @return string
     */
    public static function resolveResourceUrl(string $path): string
    {
        return Storage::disk(config('filesystems.default'))->url(config('filesystems.default') == 'local' ? Str::replace('uploads/public/', '', $path) : $path);
    }
}
