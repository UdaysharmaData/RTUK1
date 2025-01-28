<?php

namespace App\Services\DataCaching;

use App\Enums\TimeReferenceEnum;
use App\Services\ApiClient\ApiClientSettings;
use App\Services\DataCaching\Exceptions\InvalidDataRetrievalMethodException;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\SignedExternalUrlProcessor;
use App\Services\TimePeriodReferenceService;
use Illuminate\Support\Facades\Cache;

final class CacheDataManager
{
    /**
     * @var string
     */
    private string $serviceDataMasterKey;

    /**
     * @throws \Exception
     */
    public function __construct(
        public DataServiceInterface $modelService,
        public string               $method,
        public array                $arguments = [],
        public bool                 $refresh = false,
        public bool                 $uniqueToUser = false,
        public ?TimeReferenceEnum   $cachePeriod = null,
        public ?string              $customKey = null,
        public bool                 $uniqueToActiveRole = false,
        public array                $additionalKeyParams = []
    ) {
        if (
            (! method_exists($modelService, $method))
            || (! is_callable([$modelService, $method]))
        ) {
            throw new InvalidDataRetrievalMethodException("Invalid method [$method] provided.");
        }

        if (is_null($customKey)) {
            $this->customKey = $this->generateKey();
        }

        $this->serviceDataMasterKey = self::makeServiceMasterKey($this->modelService);
    }

    /**
     *
     * @param  mixed $key
     */
    public function extraKey($key): CacheDataManager
    {
        $this->customKey = $this->generateKey() . $key;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        if ($this->refresh) {
            $this->flushCachedServiceListings();
        }

        if ($this->cachePeriod) {
            $data = Cache::remember(
                $this->customKey,
                (new TimePeriodReferenceService($this->cachePeriod->value))->toCarbonInstance(),
                $this->action()
            );
        } else $data = Cache::remember($this->customKey, now()->addDay(), $this->action());

        $this->updateKeyStore();

        return $data;
    }

    /**
     * @return \Closure
     */
    public function action(): \Closure
    {
        return fn () => $this->modelService->{$this->method}(...$this->arguments);
    }

    /**
     * @return string
     */
    private function generateKey(): string
    {
        $request = request();
        $params = SignedExternalUrlProcessor::getQueryParameters($request->fullUrl());
        $orderedUrl = SignedExternalUrlProcessor::appendQueryParametersToUrl($request->url(), $params);

        if (! is_null($user = $request->user())) {
            $user->isGeneralAdmin()
                && $orderedUrl.= '+access-type=general';

            if ($this->uniqueToUser) {
                $orderedUrl.= '+user='.$user->id;
            }

            if ($this->uniqueToActiveRole) {
                $orderedUrl.= '+active-role='.$user->activeRole?->role_id;
            }
        } elseif ($token = ApiClientSettings::requestIdentifierToken()) {
            if ($this->uniqueToUser) {
                $orderedUrl.= '+request-identifier-token='.$token;
            }
        }

        if (count($this->additionalKeyParams) > 0) {
            foreach ($this->additionalKeyParams as $key => $value) {
                $orderedUrl.= "+$key=$value";
            }
        }

        return sha1($orderedUrl.'+site='.clientSiteId());
    }

    /**
     * @return void
     */
    private function updateKeyStore(): void
    {
        $keys = [];

        if (Cache::has($masterKey = $this->serviceDataMasterKey)) {
            $existingKeys = Cache::get($masterKey);
            $keys = $existingKeys;

            if (isset($existingKeys[$this->method])) {
                $keys[$this->method] = $existingKeys[$this->method];
            }
        }

        if ((! isset($keys[$this->method])) || (! in_array($this->customKey, $keys[$this->method]))) {
            $keys[$this->method][] = $this->customKey;
        }

        Cache::put($masterKey, $keys);
    }

    /**
     * @return void
     */
    public function flushCachedServiceListings(): void
    {
        if (Cache::has($this->serviceDataMasterKey)) {
            $dataKeys = Cache::get($this->serviceDataMasterKey);

            if (array_key_exists($this->method, $dataKeys)) {
                foreach ($dataKeys[$this->method] as $key => $value) {
                    Cache::delete($value);
                }

                $this->resetCacheDataKeyStore($dataKeys);
            }
        }
    }

    /**
     * @param DataServiceInterface $service
     * @return bool
     */
    public static function flushAllCachedServiceListings(DataServiceInterface $service): bool
    {
        if (Cache::has($masterKey = self::makeServiceMasterKey($service))) {
            $dataKeys = Cache::get($masterKey);

            foreach ($dataKeys as $method => $keys) {
                foreach ($keys as $key) {
                    Cache::delete($key);
                }
            }

            Cache::delete($masterKey);

            return true;
        }

        return false;
    }

    /**
     * @param mixed $dataKeys
     * @return void
     */
    private function resetCacheDataKeyStore(mixed $dataKeys): void
    {
        unset($dataKeys[$this->method]);

        Cache::put($this->serviceDataMasterKey, $dataKeys);
    }

    /**
     * @param DataServiceInterface $service
     * @return string
     */
    private static function makeServiceMasterKey(DataServiceInterface $service): string
    {
        return sha1(get_class($service));
    }
}
