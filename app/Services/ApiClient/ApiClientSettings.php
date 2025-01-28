<?php

namespace App\Services\ApiClient;

use App\Enums\CacheTypeEnum;
use App\Models\ApiClient;
use App\Modules\Setting\Models\Site;
use App\Traits\Response;
use Closure;
use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApiClientSettings
{
    use Response;

    /**
     * @var Request
     */
    private Request $request;
    /**
     * @var ApiClient
     */
    private ApiClient $client;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private mixed $config;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->config = config('apiclient');
        $this->request = $request;
    }

    /**
     * get client IP
     * @return bool
     */
    private function clientIp(): bool
    {
        return $this->request->getClientIp();
    }

    /**
     * update client state
     * @param ApiClient $newValue
     * @return void
     */
    private function setClient(ApiClient $newValue): void
    {
        $this->client = $newValue;
    }

    /**
     * run checks to validate client and token, then do authorization
     * @param Closure $next
     * @return RedirectResponse|JsonResponse|BinaryFileResponse
     * @throws Exception
     */
    public function clientRequestAuthorizationHandshake(Closure $next): RedirectResponse|JsonResponse|BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $this->ensureIpIsNotBlacklisted()
                ->ensureRequestIsEncrypted()
                ->attemptToIdentifyClient();

            return $next($this->request);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());

            return $this->error(
                $exception->getMessage(),
                $exception->getCode()
            );
        }
    }

    /**
     * check if IP is not blacklisted
     * @throws Exception
     */
    private function ensureIpIsNotBlacklisted(): ApiClientSettings
    {
        if ( ! $this->ipIsBlacklisted()) {
            return $this;
        }
        throw new Exception('Connection from client IP is not allowed.', 403);
    }

    /**
     * verify that the client request was sent over HTTPS
     * @throws Exception
     */
    private function ensureRequestIsEncrypted(): ApiClientSettings
    {
        if (config('app.env') !== 'production' ||  $this->request->isSecure()) {
            return $this;
        }
        throw new Exception('Request is not secure.', 403);
    }

    /**
     * verify the identity of the client
     * @throws Exception
     */
    private function attemptToIdentifyClient(): ApiClientSettings
    {
        $key = config('app.key');
        $clients = self::apiClients();

//        Log::info($this->request->ip());

        //\Log::info(json_encode($clients));

        $clients = $clients
            ->filter(function($client) use ($key) {
                return
                    (sha1($client->api_client_id) === $this->clientHeaderToken())
                    || (hash_hmac('sha256', $client->api_client_id, $key) === $this->clientHeaderToken()); // this ultimately replaces line above
            })->collect();

        if ($clients->isNotEmpty()) {
            $this->setClient($client = $clients->first());
            return $this;
        } else {
            Log::info('URL: '.$this->request->fullUrl());
            Log::info('Client Key: '.$this->clientHeaderToken());
            throw new Exception('Unknown API Client!', 401);
        }
    }

    /**
     * get current request client
     * @return mixed|null
     */
    public static function getCurrentClientFromCache(): mixed
    {
        $key = config('app.key');
        $clients = self::apiClients()
            ->filter(function($client) use($key) {
                return
                    (sha1($client->api_client_id) === request()->header(self::getClientKey()))
                    || (hash_hmac('sha256', $client->api_client_id, $key) === request()->header(self::getClientKey())); // this ultimately replaces line above
            })->collect();

        // if (\Illuminate\Support\Facades\Route::getFacadeRoot()->current()->uri() == "api/v1/payment/checkout/{type}/proceed") {
            // \Log::debug('Client from Cache: ' . request()->header(self::getClientKey()));
            // \Log::debug('Clients: ' . $clients);
        // }

        return ($clients->isNotEmpty())
            ? $clients->first()
            : null;
    }

    /**
     * get current request client's site
     * @return mixed|null
     */
    public static function getCurrentClientSiteFromCache(): mixed
    {
        return self::getCurrentClientFromCache()?->site;
    }

    /**
     * @param CacheTypeEnum $type
     * @return mixed|void
     */
    public static function refreshCache(CacheTypeEnum $type)
    {
        $lock = self::getLock($type);

        try {
//            $lock->block(5);
            $cacheKey = self::getComputedCacheKey($type);

            Cache::forget($cacheKey);
            return Cache::rememberForever($cacheKey, function () use($type) {
                $model = self::getComputedModelClassName($type);

                return $model::all();
            });
        } catch (LockTimeoutException|Exception $e) {
            Log::error($e->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * @param CacheTypeEnum $type
     * @param int $seconds
     * @return \Illuminate\Contracts\Cache\Lock
     */
    private static function getLock(CacheTypeEnum $type, int $seconds = 10): \Illuminate\Contracts\Cache\Lock
    {
        return Cache::lock($type->value, $seconds);
    }

    /**
     * @param CacheTypeEnum $type
     * @return mixed
     * @throws Exception
     */
    private static function getComputedCacheKey(CacheTypeEnum $type): mixed
    {
        $cacheKey = "{$type->value}CacheKey";
        if (method_exists(self::class, $cacheKey)) {
            return self::$cacheKey();
        } else throw new Exception("Trying to call an invalid method [$cacheKey]");
    }

    /**
     * @param CacheTypeEnum $type
     * @return string
     * @throws Exception
     */
    private static function getComputedModelClassName(CacheTypeEnum $type): string
    {
        return match ($type->name) {
            'ApiClient' => ApiClient::class,
            'Site' => Site::class,
            default => throw new Exception("Couldn't resolve [$type->name] class."),
        };
    }

    /**
     * get list of blacklisted IPs
     * @return array
     */
    public function ipBlacklist(): array
    {
        return $this->config['ip_blacklist'];
    }

    /**
     * @return bool
     */
    private function ipIsBlacklisted(): bool
    {
        return in_array($this->clientIp(), $this->ipBlacklist());
    }

    /**
     * @return mixed
     */
    private static function apiClients(): mixed
    {
        return Cache::rememberForever(self::clientsCacheKey(), function () {
            return ApiClient::all();
        });
    }

    /**
     * @return mixed
     */
    private static function sites(): mixed
    {
        return Cache::rememberForever(self::sitesCacheKey(), function () {
            return Site::all();
        });
    }

    /**
     * @return string
     */
    private static function clientsCacheKey(): string
    {
        return 'api-clients';
    }

    /**
     * @return string
     */
    private static function sitesCacheKey(): string
    {
        return 'sites';
    }

    /**
     * client request header has token
     * @return bool
     */
    private function clientKeyExistsInHeader(): bool
    {
        return $this->request->hasHeader(self::getClientKey());
    }

    /**
     * get client token from header
     * @return string|null
     */
    private function clientHeaderToken(): string|null
    {
        return $this->request->header(self::getClientKey());
    }

    /**
     * @return string
     */
    public static function getClientKey(): string
    {
        return 'X-Client-Key';
    }

    /**
     * get client token from header
     * @return string|null
     */
    public static function requestIdentifierToken(): string|null
    {
        return request()->header(self::getRequestIdentifierKey());
    }

    /**
     * @return string
     */
    public static function getRequestIdentifierKey(): string
    {
        return 'X-Platform-User-Identifier-Key';
    }
}
