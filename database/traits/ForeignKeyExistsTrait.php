<?php

namespace Database\Traits;

use Illuminate\Support\Facades\Schema;

trait ForeignKeyExistsTrait
{
    /**
     * Check if the foreign key exists
     *
     * @param  string  $column
     * @return bool
     */
    protected function foreignKeyExists($table, string $column): bool
    {
        $fkColumns = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys($table);

        return collect($fkColumns)->map(function ($fkColumn) {
            return $fkColumn->getColumns();
        })->flatten()->contains($column);
    }
}