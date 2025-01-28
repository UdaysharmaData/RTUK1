<?php

namespace App\Modules\User\Traits;

use App\Modules\User\Models\ReferralCode;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

trait ReferralCodeTrait
{
    /**
     * @return HasOne
     */
    public function referralCode(): HasOne
    {
        return $this->hasOne(ReferralCode::class);
    }

    /**
     * @return void
     */
    public function saveReferralCode()
    {
        $this->referralCode()->create([
            'code' => $this->generateCode()
        ]);
    }

    /**
     * @param int $length
     * @return string
     */
    public function generateCode(int $length = 10): string
    {
        return Str::upper(Str::of($this->first_name)
                ->explode(' ')
                ->first()) . '/' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'),1, $length);
    }

    /**
     * @return Application|UrlGenerator|string
     */
    public function referralLink(): string|UrlGenerator|Application
    {
        return url("register?ref={$this->referralCode->ref}");
    }
}
