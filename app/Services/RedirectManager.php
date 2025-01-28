<?php

namespace App\Services;

use App\Contracts\Redirectable;
use App\Enums\RedirectStatusEnum;
use App\Models\Redirect;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RedirectManager
{
    use Response;

    private string $redirectableAttributeName;
    private string $redirectableAttributeValue;
    private string $redirectableClassName;
    private string $redirectableTargetHost;

    public function __construct(string $redirectableClassName, string $redirectableAttributeValue, string $redirectableAttributeName = 'slug',string $redirectableTargetHost = '')
    {
        if (! is_subclass_of($redirectableClassName, Redirectable::class)) {
            throw new \InvalidArgumentException('The class must implement the Redirectable interface.');
        }

        $this->redirectableClassName = $redirectableClassName;
        $this->redirectableAttributeValue = $redirectableAttributeValue;
        $this->redirectableAttributeName = $redirectableAttributeName;
        $this->redirectableTargetHost = $redirectableTargetHost;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirect(): \Illuminate\Http\JsonResponse
    {
        if (! is_null($redirect = $this->getRedirectInstance())) {
            if (! is_null($redirect->redirect_url)) {
                return response()->json([
                    'status' => true,
                    'message' => 'Resource location changed.',
                ], $this->getStatusCode($this->getRedirectableMatchFromTrash(), $redirect), [
                    'Location' => $redirect->redirect_url
                ]);
            } elseif (empty($redirect->redirect_url)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Redirect URL not found',
                ], 404);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Resource location changed.',
                ], 410);
            }
        }

        return $this->error('No result(s) found.', 404);
    }

    /**
     * @return Redirectable|null
     */
    private function getRedirectableMatchFromTrash(): Redirectable|null
    {
        return $this->redirectableClassName::onlyTrashed()
            ->withoutEagerLoads()
            ->withoutAppends()
            ->where($this->redirectableAttributeName, '=', $this->redirectableAttributeValue)
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    private function getRedirectInstance(): null|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
    {
        $redirect=Redirect::query()
        ->where('redirectable_type', '=', $this->redirectableClassName)
        ->where("model->$this->redirectableAttributeName", '=', $this->redirectableAttributeValue)
        ->first();

        if(!is_null($redirect)){
            return $redirect;
        }
        //some workaround with path before searching
        $path = $this->getPrefixedSlugBasedOnRequestModel($this->redirectableClassName, $this->redirectableAttributeValue);
        return Redirect::query()
        ->Where('target_url', '=', $this->redirectableTargetHost.'/'. $path)
        ->orWhere("target_url", '=', $this->redirectableTargetHost.'/'.($this->normalizeSlug($path)))
        ->first();
    }


    /**
     * Normalizes a slug by removing any trailing slashes or by adding slashes.
     *
     * @param string $slug The slug to be normalized.
     * @return string The normalized slug.
     */

    private function normalizeSlug(string $slug): string
    {
       if(!Str::endsWith($slug, '/')){
            $slug=$slug.'/';
        }
        return $slug;
    }
    
    /**
     * Returns a prefixed slug based on the provided model and slug.
     *
     * @param string $model The model to determine the prefix from.
     * @param string $slug The slug to be prefixed.
     * @return string The prefixed slug.
     */
    private function getPrefixedSlugBasedOnRequestModel($model, $slug){
        
        $status = match($model) {
            'App\Modules\Event\Models\Event' => 'event',
            'App\\Modules\\Event\\Models\\EventCategory' => 'events/categories',
            'App\\Models\\Venue' => 'venues',
            'App\\Models\\Region' => 'regions',
            'App\\Models\\City' => 'cities',
            default =>'99'
        };
        if($status == '99'){
            return ltrim($slug, '/');
        }else{
            return $status.'/'.$slug;
        }
    }

    /**
     * @param Redirectable|null $match
     * @param Redirect|Model|null $redirect
     * @return int
     */
    private function getStatusCode(?Redirectable $match = null, Redirect|Model|null $redirect = null): int
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
}
