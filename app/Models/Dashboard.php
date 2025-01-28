<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dashboard extends Model
{
    protected $table = 'dashboards';

    protected $fillable = ['role_id','widgets','news_title','news_body'];

    protected $hidden = [];

    public $timestamps = false;

    public function role(): BelongsTo
    {
    	return $this->belongsTo(Role::class);
    }
}
