<?php
namespace App\Services;
use App\Contracts\Redirectable;
use App\Enums\RedirectStatusEnum;
use App\Models\Redirect;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Model;
class RedirectAllManager
{
    use Response;
    private string $redirectableAttributeName;
    private string $redirectableAttributeValue;
    private string $redirectableClassName;
    public function __construct(string $redirectableAttributeName = 'slug')
    {
        
       
        $this->redirectableAttributeName = $redirectableAttributeName;
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
                ], $this->getStatusCode($redirect), [
                    'Location' => $redirect->redirect_url
                ]);

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
        return Redirect::query()
            ->where('target_url', '=', $this->redirectableAttributeName)
            ->first();

            
            
    }
    /**
     * @param Redirectable|null $match
     * @param Redirect|Model|null $redirect
     * @return int
     */
    private function getStatusCode(Redirect|Model|null $redirect = null): int
    {
   
        $status = match ($redirect?->hard_delete?->value) {
            RedirectStatusEnum::Permanent->value => 301,
            RedirectStatusEnum::Temporal->value => 302,
            null => 410,
            default => 404,
        };
      
        return $status;
    }
}
