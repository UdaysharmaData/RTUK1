<?php

namespace App\Traits;

use App\Contracts\Redirectable;
use App\Enums\RedirectStatusEnum;
use App\Enums\RedirectTypeEnum;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait RedirectableTrait
{
    /**
     * @return MorphOne
     */
    public function redirect(): MorphOne
    {
        return $this->morphOne(Redirect::class, 'redirectable');
    }

    /**
     * @param Redirectable|null $match
     * @param Redirect|Model|null $redirect
     * @return int
     */
    public static function getStatusCode(?Redirectable $match = null, Redirect|Model|null $redirect = null): int
    {
        if (! is_null($match)) {
            $status = match ($redirect?->soft_delete?->value) {
                RedirectStatusEnum::Temporal->value => 302,
                null => 410,
                default => 404,
            };
        } else {
            $status = match ($redirect?->hard_delete?->value) {
                RedirectStatusEnum::Permanent->value => 301,
                RedirectStatusEnum::Temporal->value => 302,
                null => 410,
                default => 404,
            };
        }

        return $status;
    }

    /**
     * @return Model|null
     */
    public function addDefaultRedirect(): Model|null
    {
        if (is_null($this->redirect) && isset($this->url)) {
            $this->redirect()->create([
                'type' => RedirectTypeEnum::Single->value,
                'target_url' => $this->url,
                'model' => $this,
            ]);
        }

        return $this->redirect;
    }
}
