<?php

namespace App\Modules\Charity\Models;

use App\Modules\Setting\Enums\SiteEnum;
use App\Traits\AddUuidRefAttribute;
use App\Modules\Setting\Models\Site;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CharityProfile extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'charity_profiles';

    protected $fillable = [
        'charity_id',
        'site_id',
        'description',
        'mission_values',
        'video'
    ];

    protected $appends = [
        'video_id'
    ];

    /**
     * Get the video_id attribute.
     */
    public function getVideoIdAttribute()
    {
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->video, $match)) {
            return $match[1];
        } else {
            return $this->video;
        }
    }

    /**
     * Get the charity that owns the data.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the site that owns the data.
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // /**
    //  * Replicate the charity profile on another site.
    //  * 
    //  * // TODO: Add the route charities/profile/replicate and it's corresponding method _replicate under the CharityController or CharityProfileController (if it exists)
    //  * 
    //  * @param  SiteEnum  $site The site to replicate to
    //  * @return CharityProfile
    //  */
    // public function _replicate(SiteEnum $site): CharityProfile
    // {
    //     $newProfile = $this->replicate(['site_id']);
    //     $newProfile->site_id = $site;
    //     $newProfile->save();

    //     return $newProfile;
    // }
}
