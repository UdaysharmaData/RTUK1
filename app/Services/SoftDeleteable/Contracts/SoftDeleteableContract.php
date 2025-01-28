<?php

namespace App\Services\SoftDeleteable\Contracts;

interface SoftDeleteableContract
{
    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes();

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes();

    /**
     * @param string $key
     * @return mixed|null
     */
    public static function getActionMessage(string $key): mixed;
}
