<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\SlugTrait;
use App\Enums\MedalTypeEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\Uploadable\HasOneUpload;
use App\Models\Relations\MedalRelations;
use App\Traits\FilterableListQueryScope;
use App\Models\Traits\MedalQueryScopeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Traits\Drafts\DraftTrait;

class Medal extends Model implements CanHaveUploadableResource
{
  use HasFactory, SoftDeletes, SlugTrait, UuidRouteKeyNameTrait, AddUuidRefAttribute, HasOneUpload, MedalRelations, MedalQueryScopeTrait, FilterableListQueryScope, DraftTrait;

  protected $fillable = [
    'name',
    'medalable_id',
    'medalable_type',
    'description',
    'type',
    'site_id'
  ];

  protected $casts = [
    'type' => MedalTypeEnum::class
  ];

  protected $dates = [
    'created_at',
    'updated_at',
    'deleted_at'
  ];

  public static $actionMessages = [
    'force_delete' => 'Deleting the medal permanently will unlink it from events, event categories and others. This action is irreversible.'
  ];
}
