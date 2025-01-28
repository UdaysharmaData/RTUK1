<?php

namespace App\Services\DataExchangeService\Implementations;

use App\Services\DataExchangeService\Exceptions\InvalidApiImportUrl;
use App\Services\DataExchangeService\Importer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class ApiImporter extends Importer
{
    /**
     * @var string
     */
    public string $url;
    private array $config;

    /**
     * @throws InvalidApiImportUrl
     */
    public function __construct()
    {
        $this->config = config('importer.sources.api');

        $this->setUrl();
    }

    /**
     * @return ApiImporter
     */
    public function fetch(): ApiImporter
    {
        if (app()->runningInConsole()) {
            echo  'fetching payload from server...' . PHP_EOL;
        }

        $retry = $this->config['retry'];
        $times = $retry['times'];
        $sleepInMilliseconds = $retry['sleep_milliseconds'];

        $this->payload = Http::retry($times, $sleepInMilliseconds, function ($exception) {
            return $exception instanceof ConnectionException;
        })->acceptJson()->get($this->url);

        return $this;
    }

    /**
     * @return string
     */
    private function computedUrl(): string
    {
        $endpoint = $this->config['url'];
        $hostname = rtrim($endpoint['hostname'], '/');
        $path = ltrim($endpoint['path'], '/');

        return "$hostname/$path";
    }

    /**
     * @return bool
     */
    private function urlFormatCheckPass(): bool
    {
        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';

        return preg_match($regex, $this->computedUrl());
    }

    /**
     * @return void
     * @throws InvalidApiImportUrl
     */
    private function setUrl(): void
    {
        if ($this->urlFormatCheckPass()) {
            $this->url = $this->computedUrl();
        } else throw new InvalidApiImportUrl('Invalid [hostname] or [path] provided for API import.');
    }
}
