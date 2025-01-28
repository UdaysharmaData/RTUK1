<?php

namespace App\Traits;

use App\Models\ApiClient;

trait ClientIdAttributeGenerator
{
    /**
     * @param $model
     * @return mixed
     */
    protected static function addClientIdAttributeToModel($model): mixed
    {
        $model->api_client_id = clientId();

        return $model;
    }

    /**
     * @return void
     */
    protected static function bootClientIdAttributeGenerator()
    {
        static::creating(function ($model) {
            self::addClientIdAttributeToModel($model);
        });
    }

    /**
     * resource owner client
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }
}
