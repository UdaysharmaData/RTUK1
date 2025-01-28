<?php

namespace App\Services\SoftDeleteable\Traits;

trait ActionMessages
{
    /**
     * @param string $key
     * @return mixed|null
     */
    public static function getActionMessage(string $key): mixed
    {
        if (array_key_exists($key, $messages = (static::$actionMessages ?? []))) {
            return $messages[$key];
        }

        return null;
    }
}
