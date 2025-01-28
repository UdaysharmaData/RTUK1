<?php

namespace App\Services;

use App\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SignedExternalUrlProcessor
{
    /**
     * @var User
     */
    public User $user;

    /**
     * @var bool
     */
    public bool $hasExpired = true;

    /**
     * @param string $url
     * @param \DateTimeInterface|\DateInterval|int $expiration
     * @param array $parameters
     * @return string
     */
    public function signUrl(string $url, \DateTimeInterface|\DateInterval|int $expiration, array $parameters = []): string
    {
        if ($expiration) {
            $parameters = $parameters + ['expires' => $this->availableAt($expiration)];
        }

        ksort($parameters);
        $key = config('app.key');

        return self::appendQueryParametersToUrl($url, $parameters + [
                'signature' => hash_hmac('sha256', self::appendQueryParametersToUrl($url, $parameters), $key),
            ]);
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param \DateInterval|\DateTimeInterface|int $delay
     * @return int
     */
    protected function availableAt(\DateInterval|\DateTimeInterface|int $delay = 0): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof \DateTimeInterface
            ? $delay->getTimestamp()
            : Carbon::now()->addRealSeconds($delay)->getTimestamp();
    }

    /**
     * If the given value is an interval, convert it to a DateTime instance.
     *
     * @param \DateInterval|\DateTimeInterface|int $delay
     * @return \DateInterval|\DateTimeInterface|int
     */
    protected function parseDateInterval(\DateInterval|\DateTimeInterface|int $delay): \DateInterval|\DateTimeInterface|int
    {
        if ($delay instanceof \DateInterval) {
            $delay = Carbon::now()->add($delay);
        }

        return $delay;
    }

    /**
     * @param string $url
     * @param array $parameters
     * @return string
     */
    public static function appendQueryParametersToUrl(string $url, array $parameters): string
    {
        foreach ($parameters as $parameter => $value) {
            $index = array_search($parameter, array_keys($parameters));
            $index == 0
                ? $url.= "?$parameter=$value"
                : $url.= "&$parameter=$value";
        }
        return $url;
    }


    /**
     * Determine if the given request has a valid signature.
     *
     * @param string $fullUrl
     * @param string $controlledUrl
     * @return bool
     */
    public function hasValidSignature(string $fullUrl, string $controlledUrl): bool
    {
        $queryParams = self::getQueryParameters($fullUrl);

        if (Str::startsWith($controlledUrl, 'http://')
            || Str::startsWith($controlledUrl, 'https://' )) {

            return $this->hasCorrectSignature($fullUrl, $controlledUrl)
                && $this->signatureHasNotExpired($queryParams['expires']);
        }

        return $this->hasCorrectSignature($fullUrl, "https://$controlledUrl")
            && $this->signatureHasNotExpired($queryParams['expires']);
    }

    /**
     * @param string $fullUrl
     * @return array
     */
    public static function getQueryParameters(string $fullUrl): array
    {
        $fragments = parse_url($fullUrl, PHP_URL_QUERY);
        parse_str($fragments, $queryParams);

        foreach ($queryParams as $key => $value) {
            if (is_array($value)) {
                $queryParams[$key] = implode(',', $value);
            }
        }

        return $queryParams;
    }

    /**
     * Determine if the signature from the given request matches the URL.
     *
     * @param string $fullUrl
     * @param string $controlledUrl
     * @return bool
     */
    public function hasCorrectSignature(string $fullUrl, string $controlledUrl): bool
    {
        $queryParams = self::getQueryParameters($fullUrl);
//        $url = $this->getUrlWithPathOnlyFromFullUrl($fullUrl);
        $signature = $queryParams['signature'];
        $key = config('app.key');

        unset($queryParams['signature']);
        ksort($queryParams);

        $controlledFullUrl = self::appendQueryParametersToUrl($controlledUrl, $queryParams);
        $controlSignature = hash_hmac('sha256', $controlledFullUrl, $key);

        return hash_equals($signature, $controlSignature);
    }

    /**
     * Determine if the expires timestamp from the given request is not from the past.
     *
     * @param string $expires
     * @return bool
     */
    public function signatureHasNotExpired(string $expires): bool
    {
        $notExpired = ! ($expires && Carbon::now()->getTimestamp() > $expires);

        if ($notExpired) {
            $this->hasExpired = false;
        }

        return $notExpired;
    }

    /**
     * @param string $fullUrl
     * @return string
     */
    private function getUrlWithPathOnlyFromFullUrl(string $fullUrl): string
    {
        $urlFragments = parse_url($fullUrl);

        return "{$urlFragments['scheme']}://{$urlFragments['host']}{$urlFragments['path']}";
    }
}
