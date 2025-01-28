<?php


namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwoFactorAuthUser extends Model
{
    use HasFactory;

    protected $table = 'two_factor_auth_users';

    protected $fillable = ['default'];

}
