<?php

namespace App\Services;

use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Enums\ListTypeEnum;
use App\Enums\OrderByDirectionEnum;
use Illuminate\Support\Collection;

class DefaultQueryParamService
{
    /**
     * @var int
     */
    public int $perPage = 10;

    /**
     * @var string
     */
    private string $listing;

    /**
     * @var array
     */
    private array $params = [];

    /**
     * @param ListTypeEnum $listing
     */
    public function __construct(ListTypeEnum $listing)
    {
        $this->listing = $listing->value;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setPerPage(int $value): DefaultQueryParamService
    {
        $this->perPage = $value;

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params): DefaultQueryParamService
    {
        $this->params = $params;

        return $this;
    }


    /**
     * @return Collection
     */
    public function getDefaultQueryParams(): Collection
    {
        $enum = 'App\Enums\\' . $this->listing . 'ListOrderByFieldsEnum';
        $defaults = [
            'deleted' => ListSoftDeletedItemsOptionsEnum::Without->value,
            'per_page' => $this->perPage,
        ];

        if ($this->listing && class_exists($enum)) {
            $defaults = array_merge($defaults, [
                'order_by' => $enum::CreatedAt->value.':'.OrderByDirectionEnum::Descending->value,
            ]);
        }

        if (count($this->params) > 0) {
            $defaults = array_merge($defaults, $this->params);
        }

        return collect($defaults)->map(function ($value, $key) {
            return [
                'name' => $key,
                'value' => $value
            ];
        })->values();
    }
}
