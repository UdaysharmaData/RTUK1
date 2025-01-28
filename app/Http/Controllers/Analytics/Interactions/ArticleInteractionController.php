<?php

namespace App\Http\Controllers\Analytics\Interactions;

use App\Http\Controllers\Analytics\AnalyticsController;
use App\Models\Article;
use App\Services\Analytics\Events\AnalyticsInteractionEvent;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;

class ArticleInteractionController extends AnalyticsController
{
    use Response;

    public function __construct()
    {
        parent::__construct('interaction');
    }

    /**
     * Capture an article Interaction
     *
     * @group Analytics
     * @authenticated
     * @header Content-Type application/json
     * @header X-Platform-User-Identifier-Key RTHUB.v1.98591b54-db61-46d4-9d29-47a8a7f325a8.1675084780
     *
     * @urlParam article_ref string required The ref attribute of the article. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param Article $article
     * @return JsonResponse
     */
    public function __invoke(Article $article): JsonResponse
    {
        AnalyticsInteractionEvent::dispatch($article);

        return $this->success('Article interaction registered.', 200, [
            'article' => $article->fresh()
        ]);
    }
}
