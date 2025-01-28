<?php

namespace App\Http\Controllers\Client;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Setting\Models\Site;
use App\Services\RedirectAllManager;
use App\Traits\SingularOrPluralTrait;
use App\Services\DataServices\EventClientDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Validator;

class RedirectController extends Controller
{
    use Response, SiteTrait, UploadTrait, SingularOrPluralTrait;
    private Site|null $site;

    public function __construct(protected EventClientDataService $eventService)
    {
        parent::__construct();

        $this->site = static::getSite();
    }

    /**
     * Redirect All
     *
     * Handle a redirect request to the application.
     *
     * @group Authentication
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam target_url string required The target_url of the Redirect. Example: events
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function redirect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_url' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }
        try {

            $target_url = $request->headers->get('referer').$request->target_url;
    
            return (new RedirectAllManager($target_url))->redirect();
    
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('An error occurred while getting event details.', 500);
        }
    }

    public function directRedirectUrl(Request $request)
    {
        try {
            $url = $request->input('url');
            $directRedirect = DB::table('redirects')
                ->select('id', 'redirect_url', 'soft_delete', 'hard_delete')
                ->where('site_id', clientSiteId())
                ->where('target_url', $url)
                ->first();

            if ($directRedirect) {
                if ($directRedirect->redirect_url) {
                    $statusCode = 302;
                    if ($directRedirect->soft_delete === 'temporal') {
                        $statusCode = 301;
                    } elseif ($directRedirect->hard_delete === 'permanent') {
                        $statusCode = 302;
                    }
                    if (in_array($statusCode, [301, 302])) {
                        return redirect()->to($directRedirect->redirect_url, $statusCode);
                    } else {
                        return $this->error('Invalid status code for redirection.', 400);
                    }
                }
            } else {
                return $this->error('An error occurred while getting redirect details.', 400);
            }

            return redirect()->back()->with('error', 'Redirect URL not found.');
        } catch (\Exception $e) {
            return $this->error('An unexpected error occurred: ' . $e->getMessage(), 500);
        }
    }
}
