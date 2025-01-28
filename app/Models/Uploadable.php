<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Enums\UploadUseAsEnum;
use App\Services\DataCaching\Traits\CacheQueryBuilder;

class Uploadable extends Model
{
    use HasFactory, CacheQueryBuilder;
    
    /**
     * table
     *
     * @var string
     */
    protected $table = 'uploadables';
    
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'use_as',
        'upload_id',
        'uploadable_id',
        'uploadable_type',
    ];
    
    /**
     * casts
     *
     * @var array
     */
    protected $casts = [
        'use_as' => UploadUseAsEnum::class,
    ];

    /**
     * upload
     *
     * @return BelongsTo
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }
    
    protected static function booted()
    {
        static::deleting(function ($model) {
            if (Uploadable::where('upload_id', '=', $model->upload_id)->where('id', '!=', $model->id)->doesntExist()) {
                $model->upload->delete();
            }
        });
    }
}
