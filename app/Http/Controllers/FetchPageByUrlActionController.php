<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageUrlRequest;
use App\Models\Page;
use App\Models\Redirect;
use App\Services\RedirectManager;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class FetchPageByUrlActionController
{
    use Response;

    /**
     * Fetch Page by URL
     *
     * Retrieve page data matching specified URL.
     *
     * @group Pages
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam url string required The url attribute of the page. Example: https://page-one.test
     *
     * @param PageUrlRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(PageUrlRequest $request): JsonResponse
    {
        $url = $request->validated('url');

        try {
            $page = Page::query()
                ->withoutEagerLoad(['faqs'])
                ->where('url', '=', $url)
                ->firstOrFail();

            $redirect = Redirect::query()
                ->where('redirectable_type', '=', Page::class)
                ->where('model->url', '=', $url)
                ->first();

            if (! is_null($redirect)) {
                if (isset($redirect->redirect_url)) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Resource location changed.',
                    ], Page::getStatusCode($page, $redirect), [
                        'Location' => $redirect->redirect_url
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Resource location changed.',
                    ], 410);
                }
            }

            return $this->success('Page data retrieved.', 200, [
                'page' => $page
            ]);
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(Page::class, $url, 'url',$origin))->redirect();
        } catch (\Exception $e) {
            return $this->error('An error occurred while fetching page data.', 500);
        }
    }
}
