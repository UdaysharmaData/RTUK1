<?php

namespace App\Services\SoftDeleteable;

use App\Contracts\Redirectable;
use App\Services\SignedExternalUrlProcessor;
use App\Services\SoftDeleteable\Contracts\SoftDeleteableContract;
use App\Services\SoftDeleteable\Exceptions\DeletionConfirmationRequiredException;
use App\Services\SoftDeleteable\Exceptions\InvalidDeleteableColumnException;
use App\Services\SoftDeleteable\Exceptions\InvalidSignatureForHardDeletionException;
use App\Services\SoftDeleteable\Exceptions\SoftDeleteableClassNotFoundException;
use App\Services\SoftDeleteable\Exceptions\SoftDeletionNotSupportedOnClassException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SoftDeleteableManagementService
{
    /**
     * @var string
     */
    private string $softDeleteableClass;
    /**
     * @var string
     */
    private string $tableColumn;
    /**
     * @var string
     */
    private string $forceDeletionWarningMessage;
    /**
     * @var array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\Request|mixed|string|null
     */
    private mixed $request;

    /**
     * @throws \Exception
     */
    public function __construct(string $softDeleteableClass, string $tableColumn = 'id')
    {
        if (
            (! class_exists($softDeleteableClass))
            || (! (new $softDeleteableClass instanceof Authenticatable || new $softDeleteableClass instanceof Model))
        ) {
            throw new SoftDeleteableClassNotFoundException("Invalid class [$softDeleteableClass] provided.");
        }

        if (! in_array(SoftDeletes::class, class_uses($softDeleteableClass), true)) {
            throw new SoftDeletionNotSupportedOnClassException("Model [$softDeleteableClass] does not appear to support soft-deletion.");
        }

        if (! ((new $softDeleteableClass) instanceof SoftDeleteableContract)) {
            throw new SoftDeleteableClassNotFoundException("Action messages not setup on [$softDeleteableClass].");
        }

        if (! Schema::hasColumn((new $softDeleteableClass)->getTable(), $tableColumn)) {
            throw new InvalidDeleteableColumnException("Invalid column [$tableColumn] provided.");
        }

        $this->softDeleteableClass = $softDeleteableClass;
        $this->tableColumn = $tableColumn;

        $this->forceDeletionWarningMessage = $softDeleteableClass::getActionMessage('force_delete');
        $this->request = request();
    }

    /**
     * @param array $values
     * @param string $deleteTypeParam
     * @return bool
     * @throws DeletionConfirmationRequiredException
     * @throws InvalidSignatureForHardDeletionException
     */
    public function delete(array $values, string $deleteTypeParam): bool
    {
        $query = $this->softDeleteableClass::whereIn($this->tableColumn, $values);
        $this->deleteTypeParam = $deleteTypeParam;

        if ($force = $this->request->get($this->deleteTypeParam) == 1) {
            if ($this->request->has(['signature', 'expires', 'user'])) {
                if ($this->request->hasValidSignature() && $this->isOriginalPayload($values)) {
                    $this->setupDefaultRedirectIfRedirectable($query);

                    $query->forceDelete();
                } else throw new InvalidSignatureForHardDeletionException;
            } else {
                throw new DeletionConfirmationRequiredException(
                    $this->forceDeletionWarningMessage,
                    400,
                    null,
                    $this->getHardDeleteAuthorizationParams($deleteTypeParam, $values)
                );
            }
        } else {
            $this->setupDefaultRedirectIfRedirectable($query);
            $query->delete();
        }

        return true;
    }

    /**
     * @param array $values
     * @return bool
     */
    public function restore(array $values): bool
    {
        $this->softDeleteableClass::onlyTrashed()
            ->whereIn($this->tableColumn, $values)
            ->restore();

        return true;
    }

    /**
     * @param string $deleteTypeParam
     * @param array $values
     * @param int $minutes
     * @return array
     */
    private function getHardDeleteAuthorizationParams(string $deleteTypeParam, array $values, int $minutes = 1): array
    {
        return [
            'confirmation' => [
                'url' => $url = (new SignedExternalUrlProcessor)->signUrl(
                    $this->request->url(),
                    now()->addMinutes($minutes),
                    [
                        'user' => $this->request->user()->ref,
                        $deleteTypeParam => 1,
                        'pl' => $this->encodeValues($values)
                    ]
                ),
                'query_params' => Str::after($url, '?'),
                'validity' => "$minutes ".Str::plural('minute', $minutes)
            ],
        ];
    }

    /**
     * @param array $values
     * @return string
     */
    private function encodeValues(array $values): string
    {
        return sha1(json_encode($values));
    }

    /**
     * @param array $values
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function isOriginalPayload(array $values): bool
    {
        return $this->request->get('pl') === $this->encodeValues($values);
    }

    /**
     * @param $query
     * @return void
     */
    private function setupDefaultRedirectIfRedirectable($query): void
    {
        if (new $this->softDeleteableClass instanceof Redirectable) {
            $query->get()->each(function (Redirectable $item) {
                $item->addDefaultRedirect();
            });
        }
    }
}
