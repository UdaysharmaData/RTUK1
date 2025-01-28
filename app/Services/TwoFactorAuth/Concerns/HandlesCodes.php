<?php

namespace App\Services\TwoFactorAuth\Concerns;

use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use ParagonIE\ConstantTime\Base32;
use Psr\SimpleCache\InvalidArgumentException;

trait HandlesCodes
{
    protected Repository $cache;

    protected string $prefix;

    /**
     * Initializes the current trait.
     *
     * @throws Exception
     */
    protected function initializeHandlesCodes(): void
    {
        ['store' => $store, 'prefix' => $this->prefix] = config('two-factor.cache');

        $this->cache = $this->useCacheStore($store);
    }

    /**
     * Returns the Cache Store to use.
     *
     * @param  string|null  $store
     * @return Repository
     *
     * @throws Exception
     */
    protected function useCacheStore(string $store = null): Repository
    {
        return cache()->store($store);
    }

    /**
     * Validates a given code, optionally for a given timestamp and future window.
     *
     * @param string $code
     * @param DateTimeInterface|int|string $at
     * @param int|null $window
     * @param string $methodRef
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateCode(string $code, DateTimeInterface|int|string $at = 'now', int $window = null, string $methodRef = ''): bool
    {
        if ($this->codeHasBeenUsed($code)) {
            return false;
        }

        $window ??= $this->window;

        for ($i = 0; $i <= $window; $i++) {
            if (hash_equals($this->makeCode($at, -$i, methodRef: $methodRef), $code)) {
                $this->setCodeAsUsed($code, $at);

                return true;
            }
        }

        return false;
    }


    /**
     * Creates a Code for a given timestamp, optionally by a given period offset.
     *
     * @param DateTimeInterface|int|string $at
     * @param int $offset
     * @param string $methodRef
     * @return string
     */
    public function makeCode(DateTimeInterface|int|string $at = 'now', int $offset = 0, string $methodRef = ''): string
    {
        $timestamp = $this->getTimestampFromPeriod($at, $offset);
        $hmac = hash_hmac(
            $this->algorithm,
            $this->timestampToBinary($this->getPeriodsFromTimestamp($timestamp)),
            "{$this->getBinarySecret()}$methodRef",
            true
        );

        $offset = ord($hmac[strlen($hmac) - 1]) & 0xF;

        $number = (
                ((ord($hmac[$offset + 0]) & 0x7F) << 24) |
                ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
                ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
                (ord($hmac[$offset + 3]) & 0xFF)
            ) % (10 ** $this->digits);

        return str_pad((string) $number, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a temporal token valid for given period
     * @return string
     */
    public function generateTwoFactorToken(): string
    {
        $this->seconds = 3600 * config('two-factor.token_expiration_time');
        $timestamp = $this->getTimestampFromPeriod('now', 0);

        return hash_hmac(
            $this->algorithm,
            $this->timestampToBinary($this->getPeriodsFromTimestamp($timestamp)),
            "{$this->getBinarySecret()}",
        );
    }

    /**
     * Validates a given token
     * @param $token
     * @return bool
     */
    public function validTwoFactorToken($token): bool
    {
        for ($i = 0; $i <= $this->window; $i++) {
            if (hash_equals($this->generateTwoFactorToken(), $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the periods elapsed from the given Timestamp and seconds.
     *
     * @param  int  $timestamp
     * @return int
     */
    protected function getPeriodsFromTimestamp(int $timestamp): int
    {
        return (int) (floor($timestamp / $this->seconds));
    }

    /**
     * Creates a 64-bit raw binary string from a timestamp.
     *
     * @param  int  $timestamp
     * @return string
     */
    protected function timestampToBinary(int $timestamp): string
    {
        return pack('N*', 0).pack('N*', $timestamp);
    }

    /**
     * Returns the Shared Secret as a raw binary string.
     *
     * @return string
     */
    protected function getBinarySecret(): string
    {
        return Base32::decodeUpper($this->shared_secret);
    }

    /**
     * Get the timestamp from a given elapsed "periods" of seconds.
     *
     * @param DateTimeInterface|int|string|null  $at
     * @param  int  $period
     * @return int
     */
    protected function getTimestampFromPeriod(DatetimeInterface|int|string|null $at, int $period): int
    {
        $periods = ($this->parseTimestamp($at) / $this->seconds) + $period;

        return (int) $periods * $this->seconds;
    }

    /**
     * Normalizes the Timestamp from a string, integer or object.
     *
     * @param DateTimeInterface|int|string  $at
     * @return int
     */
    protected function parseTimestamp(DatetimeInterface|int|string $at): int
    {
        return is_int($at) ? $at : Carbon::parse($at)->getTimestamp();
    }

    /**
     * Returns the cache key string to save the codes into the cache.
     *
     * @param  string  $code
     * @return string
     */
    protected function cacheKey(string $code): string
    {
        return implode('|', [$this->prefix, $this->getKey(), $code]);
    }

    /**
     * Checks if the code has been used.
     *
     * @param string $code
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function codeHasBeenUsed(string $code): bool
    {
        return $this->cache->has($this->cacheKey($code));
    }

    /**
     * Sets the Code has used, so it can't be used again.
     *
     * @param string $code
     * @param DateTimeInterface|int|string $at
     * @return void
     * @throws InvalidArgumentException
     */
    protected function setCodeAsUsed(string $code, DateTimeInterface|int|string $at = 'now'): void
    {
        $timestamp = Carbon::createFromTimestamp($this->getTimestampFromPeriod($at, $this->window + 1));

        // We will safely set the cache key for the whole lifetime plus window just to be safe.
        // @phpstan-ignore-next-line
        $this->cache->set($this->cacheKey($code), true, $timestamp);
    }
}
