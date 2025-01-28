<?php

namespace App\Services\ApiClient;

use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ApiPlatformUserIdentifierValidator
{
    public function __construct(
        protected string $identifier,
        protected string $platform
    ) {}

    /**
     * @return string|null
     */
    public function validated(): ?string
    {
        if ($this->passes($this->preparedData())) {
            return $this->identifier;
        }

        return null;
    }

    /**
     * @return array
     */
    private function preparedData(): array
    {
        $data = explode('.', $this->identifier);

        return $this->toArray($data);
    }

    /**
     * @param array $data
     * @return bool
     */
    private function passes(array $data): bool
    {
        try {
            if (count($data) === 0) {
                throw new \Exception("Invalid Request Origin Identifier.");
            }

            if (
                Site::where('code', $code = $data['platform_code'])->doesntExist()
                || $code !== $this->platform
            ) {
                throw new \Exception("Invalid Request Origin Identifier Platform Code [$code].");
            }

            if ($data['api_version'] !== config('app.api_version')) {
                throw new \Exception("Invalid Request Origin Identifier API Version [{$data['api_version']}].");
            }

            if (strlen($key = $data['assigned_key']) !== 36) {
                throw new \Exception("Invalid Request Origin Identifier Assigned Key [$key].");
            }

            if (
                is_null($timestamp = $data['timestamp'])
                || Carbon::createFromTimestamp($timestamp) > now()
            ) {
                throw new \Exception("Invalid Request Origin Identifier Timestamp [$timestamp].");
            }

            return true;
        } catch (\Exception $exception) {
            Log::error($exception);

            return false;
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function toArray(array $data): array
    {
        return [
            'platform_code' => strtolower($data[0]) ?? null,
            'api_version' => $data[1] ?? null,
            'assigned_key' => $data[2] ?? null,
            'timestamp' => $data[3] ?? null
        ];
    }

    /**
     * @return array
     */
    public function validatedArray(): array
    {
        $data = explode('.', $this->validated());

        return $this->toArray($data);
    }
}
