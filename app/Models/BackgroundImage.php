<?php

namespace App\Models;

use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Modules\User\Models\Profile;
use App\Traits\Uploadable\HasOneUpload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundImage extends Model implements CanHaveUploadableResource
{
    use HasFactory, HasOneUpload;

    /**
     * @var string[]
     */
    protected $with = [
        'upload'
    ];
    protected $fillable = ['profile_id'];

    /**
     * @return BelongsTo
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
