<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Collection;

class EnquirySettings
{
    /**
     * @var string|null
     */
    protected ?string $siteCode;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private array $settings;

    public function __construct()
    {
        $this->siteCode = clientSiteCode();
        $this->settings = (array) config("apiclient.$this->siteCode.enquiries") ?? [];
    }

    /**
     * @param string $code
     * @return self
     */
    public function setSiteCode(string $code): self
    {
        $this->siteCode = $code;

        return $this;
    }

    /**
     * @return Collection
     */
    public function options(): Collection
    {
        $options = isset($this->settings['category_emails']) ? array_map(function ($item) {
            return [
                'label' => $item,
                'value' => $item
            ];
        }, $this->categories()) : [];

        return collect($options);
    }

    /**
     * @return array
     */
    public function categories(): array
    {
        return array_keys($this->settings['category_emails']) ?? [];
    }

    /**
     * @param string $category
     * @return mixed|null
     */
    public function getCategoryEmail(string $category): mixed
    {
        return $this->settings['category_emails'][$category] ?? null;
    }
}
