<?php

namespace App\Services\PasswordProtectionPolicy\Traits;

use App\Services\PasswordProtectionPolicy\Models\Passphrase;
use App\Services\PasswordProtectionPolicy\Models\PasswordRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait PasswordHistory
{
    /**
     * @return HasMany
     */
    public function passwordRecords(): HasMany
    {
        return $this->hasMany(PasswordRecord::class);
    }

    /**
     * @return void
     */
    public function deletePasswordHistory(): void
    {
        $keep = $this->pppConfig['keep'];

        $ids = $this->passwordRecords()
            ->pluck('id')
            ->sort()
            ->reverse();

        if ($ids->count() < $keep) {
            return;
        }

        $delete = $ids->splice($keep);

        $this->passwordRecords()
            ->whereIn('id', $delete)
            ->delete();
    }

    /**
     * @param Builder $query
     * @param null $daysFromNow
     * @return Builder
     */
    public function scopeWithExpiringPasswords(Builder $query, $daysFromNow = null): Builder
    {
        $days = $daysFromNow ?: $this->pppConfig['days_before_expiry_reminder'];

        return $query->whereHas('passwordRecords', function (Builder $query) use ($days) {
            $query->latest()->limit(1)
                ->whereDate('expires_at', today()->addDays($days));
        });
    }

    /**
     * determine age allowed before expiry for model/user type [based on role]
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function getPasswordAgeForRole(): mixed
    {
        return $this->isAdmin()
            ? $this->pppConfig['max_password_age']['admin']
            : $this->pppConfig['max_password_age']['user'];
    }

    /**
     * check if password is not found in recent history
     * @param mixed $value
     * @return bool
     */
    public function notInRecentHistory(mixed $value): bool
    {
        $passwordHistories = $this->passwordRecords()
            ->latest()
            ->take($this->pppConfig['keep'])
            ->get();

        foreach ($passwordHistories as $passwordHistory) {
            if (Hash::check($value, $passwordHistory->password)) {
                return false;
            }
        }

        return true;
    }

    /**
     * check if password does not contain elements in name/username
     * @param string $value
     * @return bool
     */
    public function doesNotContainElementsInNames(string $value): bool
    {
        $passed = true;

        foreach ($this->getScreeningAttributes() as $attribute) {
            if (Str::contains($value, $attribute, true)) {
                $passed = false;
                break;
            }
        }

        return $passed;
    }

    /**
     * check if provided password has expired
     * @return bool
     */
    public function currentPasswordHasExpired(): bool
    {
        $currentUserPassword = $this->passwordRecords()
            ->latest()
            ->first();

        return (bool) $currentUserPassword?->hasExpired();
    }

    /**
     * the implementing model [e.g. User] can have a Passphrase
     * @return HasOne
     */
    public function passphrase(): HasOne
    {
        return $this->hasOne(Passphrase::class);
    }

    /**
     * list attributes to be screened out of passphrase
     * @return array
     */
    protected function getScreeningAttributes(): array
    {
        return collect([
            $this->first_name,
            $this->last_name,
            $this->profile->username
        ])->filter()->toArray();
    }

    /**
     * does a user have a cache passphrase token?
     * @return bool
     */
    public function verifyPassphrase(): bool
    {
        return Cache::has($this->getCacheKey());
    }

    /**
     * get encoded passphrase cache key
     * @return string
     */
    private function getCacheKey(): string
    {
        $suffix = (string) $this->id;
        $key = "passphrase_{$suffix}";

        return sha1($key);
    }

    /**
     * create and cache a token when a user passphrase has been verified
     * @return void
     */
    public function cacheTempPassToken()
    {
        Cache::put(
            $this->getCacheKey(),
            Str::random(),
            $this->getCacheDuration()
        );
    }

    /**
     * get caching duration for passphrase
     * @return \Illuminate\Support\Carbon
     */
    private function getCacheDuration(): \Illuminate\Support\Carbon
    {
        return now()->addMinutes($this->pppConfig['passphrase_cache_mins']);
    }

    /**
     * @param string $password
     * @return void
     */
    public function createPasswordRecord(string $password): void
    {
        $this->passwordRecords()->create([
            'password' => $password,
            'expires_at' => now()->addDays($this->getPasswordAgeForRole())
        ]);
    }
}
