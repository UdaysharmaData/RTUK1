<?php

namespace App\Filters;

use App\Enums\RoleNameEnum;

class AudiencesAuthorFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'author'
    ];

    /**
     * @param string $field
     * @return void
     */
    public function author(string $field): void
    {
        $this->builder->when(
            $role = RoleNameEnum::tryFrom($field)?->value,
            fn($query) => $query->whereHas('author', function ($query) use ($role) {
                $query->whereHas('roles', function ($query) use ($role) {
                    $query->where('name', '=', $role);
                });
            })
        );
    }
}
