<?php

namespace App\Modules\User\Models;

use App\Modules\Setting\Models\Site;
use App\Services\DataCaching\Traits\CacheQueryBuilder;
use App\Traits\SiteIdAttributeGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveRole extends Model
{
    use HasFactory, CacheQueryBuilder, SiteIdAttributeGenerator;

    /**
     * @var string[]
     */
    protected $fillable = ['role_id', 'user_id', 'site_id'];
    /**
     * @var string[]
     */
    protected $with = [
        'role'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
