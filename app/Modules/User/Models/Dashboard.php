<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dashboard extends Model
{
    // Todo: may need to scrap...check DashboardController
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['role_id', 'widgets', 'news_title', 'news_body'];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return HasOne
     */
    public function role(): HasOne
    {
        return $this->hasOne(Role::class);
    }
}
