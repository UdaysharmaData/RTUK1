<?php

namespace App\Http\Controllers\Analytics\Views;

use App\Http\Controllers\Analytics\AnalyticsController;
use App\Models\Page;
use App\Services\Analytics\Events\AnalyticsViewEvent;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;

class PageViewController extends AnalyticsController
{
    use Response;

    public function __construct()
    {
        parent::__construct('views');
    }

    /**
     * Capture a page view
     *
     * @group Analytics
     * @authenticated
     * @header Content-Type application/json
     * @header X-Platform-User-Identifier-Key RTHUB.v1.98591b54-db61-46d4-9d29-47a8a7f325a8.1675084780
     *
     * @urlParam page_ref string required The ref attribute of the page. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param Page $page
     * @return JsonResponse
     */
    public function __invoke(Page $page): JsonResponse
    {
        AnalyticsViewEvent::dispatch($page);

        return $this->success('Page view registered.', 200, [
            'page' => $page->fresh()
        ]);
    }
}
