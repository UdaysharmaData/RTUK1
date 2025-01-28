<?php

namespace App\Models;

use App\Contracts\CanHaveManyBookmarks;
use App\Contracts\CanHaveManyComments;
use App\Contracts\CanHaveManyInteractions;
use App\Contracts\CanHaveManyLikes;
use App\Contracts\CanHaveManyTags;
use App\Contracts\CanHaveManyViews;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\User;
use App\Traits\AddUuidRefAttribute;
use App\Traits\HasAnalyticsTotalCountData;
use App\Traits\HasManyBookmarks;
use App\Traits\HasManyComments;
use App\Traits\HasManyInteractions;
use App\Traits\HasManyLikes;
use App\Traits\HasManyTags;
use App\Traits\HasManyViews;
use App\Traits\SlugTrait;
use App\Traits\Uploadable\HasOneUpload;
use App\Traits\UseClientGlobalScope;
use App\Traits\UseDynamicallyAppendedAttributes;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\ClientIdAttributeGenerator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mtownsend\ReadTime\ReadTime;

class Article extends Model
    implements CanHaveUploadableResource,
    CanUseCustomRouteKeyName,
    CanHaveManyLikes,
    CanHaveManyBookmarks,
    CanHaveManyTags,
    CanHaveManyComments,
    CanHaveManyViews,
    CanHaveManyInteractions
{
    use HasFactory,
        HasOneUpload,
        ClientIdAttributeGenerator,
        SlugTrait,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        UseClientGlobalScope,
        HasManyLikes,
        HasManyBookmarks,
        HasManyTags,
        HasManyComments,
        HasManyViews,
        HasManyInteractions,
        HasAnalyticsTotalCountData,
        UseDynamicallyAppendedAttributes;

    /**
     * validation rules
     */
    const RULES = [
        'create_or_update' => [
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:200'],
            'is_published' => ['required', 'boolean'],
        ]
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'ref',
        'title',
        'slug',
        'body',
        'is_published'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_published' => 'boolean'
    ];

    /**
     * @var string[]
     */
    protected $appends = ['read_time', 'preview_texts'];

    /**
     * @var string[]
     */
    protected $withCount = ['views', 'comments', 'likes'];

    /**
     * @var string[]
     */
    protected $with = ['tags', 'upload'];

    /**
     * @return string
     */
    public function getRouteKeyName() :string
    {
        return 'ref';
    }

    public static function slugAttribute(): string
    {
        return 'title';
    }

    /**
     * @return Attribute
     */
    public function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->attributes['created_at'])->diffForHumans(),
        );
    }

    /**
     * @return bool
     */
    public function getUserBookmarkedAttribute(): bool
    {
        if (! auth()->check() || $this->bookmarks()->doesntExist()) {
            return false;
        }

        return $this->bookmarks()
            ->whereHas('user', fn($q) =>  $q->whereId(auth()->id()))
            ->exists();
    }

    /**
     * Get article read-time
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function readTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (new ReadTime($this->attributes['body']))->get(),
        );
    }

    /**
     * Get body snippet
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function previewTexts(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Str::of($this->attributes['body'])->limit(),
        );
    }

    /**
     * @return BelongsTo
     */
    public function author() :BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopePublished($query): mixed
    {
        return $query->where('is_published', true);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeDraft($query): mixed
    {
        return $query->where('is_published', false);
    }

    /**
     * @return void
     */
    public function bookmark()
    {
        $this->bookmarks()->create(['user_id' => auth()->id()]);
    }

    /**
     * @return void
     */
    public function view()
    {
        if (! $this->alreadyViewedByUser()) {
            $this->views()->create();
        }
    }

    /**
     * @return bool
     */
    protected function alreadyViewedByUser(): bool
    {
        return $this->views()
            ->where('ip', request()->ip())
            ->where('agent', request()->userAgent())
            ->when(auth()->check(), fn ($query) => $query->where('user_id', request()->user()->id))
            ->exists();
    }
}
