<?php

namespace App\Modules\User\Models;

use App\Models\Upload;
use App\Enums\GenderEnum;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Traits\HasBackgroundImage;
use App\Enums\ProfileEthnicityEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uploadable\HasOneUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Uploadable\HasBackgroundImageUpload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Models\Relations\ProfileRelations;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;

class Profile extends Model implements CanHaveUploadableResource, CanUseCustomRouteKeyName
{
    use HasFactory, HasOneUpload, UuidRouteKeyNameTrait, AddUuidRefAttribute, ProfileRelations, HasBackgroundImage;

    const RULES = [
        'create_or_update' => [
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'string'],
            'username' => ['nullable', 'string', 'unique:profiles'],
            'country' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string'],
            'is_public' => ['nullable'],
            'company' => ['nullable', 'string'],
            'charity_id',// Todo: This should probably move to User
            'default_site_id', // Todo: This should probably move to User
            'stripe_id' => ['nullable', 'string'],
//            'participant_authorised',
            'external_enquiry_notification_settings', // // Todo: What's going on here?
//            'verification_token',
//            'verified',
            'fundraising_url' => ['nullable', 'string'],
//            'profile_picture',
            'slogan' => ['nullable', 'string'],
            'club' => ['nullable', 'string']
        ]
    ];

    /**
     * user's gender
     */
    const GENDER = [
        'male' => 'Male',
        'female' => 'Female',
        'others' => 'Others'
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'username',
        'gender',
        'dob',
        'address',
        'city',
        'region',
        'state',
        'postcode',
        'country',
        'nationality',
        'occupation',
        'passport_number',
        'bio',
        'ethnicity',
        'is_public',
//         'company',
//         'role_id', // move
//         'charity_id', //
//         'default_site_id',
// //        'remember_token', // Todo: move to User
//         'stripe_id',
// //        'temp_pass', // Todo: move to User
//         'participant_authorised',
//         'external_enquiry_notification_settings',
//         'verification_token',
//         'verified',
//         'fundraising_url',
//         'profile_picture',
//         'slogan',
//         'club'
    ];

    /**
     * @var string[]
     */
    protected $with = ['backgroundImage', 'upload'];

    /**
     * @var string[]
     */
    protected $casts = [
        'gender' => GenderEnum::class,
        'ethnicity' => ProfileEthnicityEnum::class,
        'is_public' => 'boolean',
        'dob' => 'immutable_date'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'avatar_url',
        'background_image_url',
        'birthday',
        'age',
        'month_born_in'
    ];

    /**
     * @return Attribute
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->avatar()
        );
    }

//    /**
//     * Set/get the dob
//     *
//     * @return Attribute
//     */
//    protected function dob(): Attribute
//    {
//        return Attribute::make(
//            set: fn ($value) => Carbon::createFromFormat('d-m-Y', $value)
//        );
//    }

    /**
     * @return Attribute
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->dob?->age
        );
    }

    /**
     * @return Attribute
     */
    protected function birthday(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->dob?->format('F j')
        );
    }

    /**
     * Get the month the user was born in
     *
     * @return Attribute
     */
    protected function monthBornIn(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->dob?->format('F')
        );
    }

    /**
     * @return string
     */
    public function avatar(): string
    {
        return $this->upload->storage_url
            ?? self::defaultAvatarUrl();
    }

    /**
     * @return Application|UrlGenerator|string
     */
    public static function defaultAvatarUrl(): string|UrlGenerator|Application
    {
        $path = config('app.images_path').'/default-avatar.png';
        return Storage::disk(config('filesystems.default'))->url(config('filesystems.default') == 'local' ? Str::replace('uploads/public/', '', $path) : $path);
    }

    /**
     * @return Attribute
     */
    protected function backgroundImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->bgImage()
        );
    }

    /**
     * @return string
     */
    public function bgImage(): string
    {
        return $this->backgroundImage?->upload?->storage_url
            ?? self::defaultBackgroundImageUrl();
    }

    /**
     * @return Application|UrlGenerator|string
     */
    public static function defaultBackgroundImageUrl(): string|UrlGenerator|Application
    {
        $path = config('app.images_path').'/default-background-image.jpg';
        return Storage::disk(config('filesystems.default'))->url(config('filesystems.default') == 'local' ? Str::replace('uploads/public/', '', $path) : $path);
    }
}
