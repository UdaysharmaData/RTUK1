<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Models\Relations\SitemapRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sitemap extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, SitemapRelations;

    protected $table = 'sitemaps';

    protected $fillable = [
        'class_name',
        'site_id',
        'latest_updated_at',
        'oldest_updated_at'
    ];

    protected $casts = [
        'latest_updated_at' => 'datetime',
        'oldest_updated_at' => 'datetime'
    ];
}
