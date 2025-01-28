<?php

namespace App\Http\Controllers;

use App\Enums\UploadTypeEnum;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Tag;
use App\Services\FileManager\FileManager;
use App\Services\FileManager\Traits\SingleUploadModel;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ArticleController extends Controller
{
    use Response, SingleUploadModel, UploadModelTrait;

    /**
     * Blog Posts
     *
     * Get paginated blog posts.
     *
     * @group Blog
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @queryParam published string Specifying the inclusion of only published posts. Example: 1
     * @queryParam search string Specifying a keyword similar to title, or body of post. Example: The Lake
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam order_by string Specifying method of ordering query. Example: popular,oldest,latest
     * @queryParam tag string Specifying posts with specific tag association. Example: sports
     *
     *
     * @return JsonResponse
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        try {
            $search = request('search');
            $perPage = request('per_page');
            $published = request('published');
            $orderBy = request('order_by'); // popular, latest, oldest
            $tag = request('tag');
            $query = Article::query();
            $parameters = array_filter(request()->query());

            $articles = $query->when($orderBy == 'latest', fn($query) => $query->latest())
                ->when($orderBy == 'oldest', fn($query) => $query->oldest())
                ->when($orderBy == 'popular', fn($query) => $query->orderByDesc('views_count'))
                ->when($search, $this->applySearchTermFilter($search))
//                ->when($published == 1, fn($query) => $query->published())
                ->when($tag, $this->applyTagFilter($tag))
                ->when($perPage, fn($query) => $query->paginate((int) $perPage))
                ->paginate(10)
                ->withQueryString();
        } catch (NotFoundExceptionInterface $e) {
            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            return $this->error('An error occurred while fetching posts', 400);
        }

        return $this->success('The list of articles', 200, [
            'articles' => $articles
        ]);
    }

    /**
     * Store Post
     *
     * Store new blog post.
     *
     * @group Blog
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam title string required The name of the blog post. Example: Amazing Spider-man
     * @bodyParam body string required The body of the blog post. Example: Some story goes here...
     * @bodyParam is_published boolean required Specify whether to publish or not. Example: true
     * @bodyParam tags string[] required Specify up to 5 tags for the post (1 minimum). Example: ["running", "charity"]
     * @bodyParam cover_image string required The cover image
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        try {
            $article = Article::create($request->validated());

            $newTags = $this->getNewTags($request, $article);

            $this->attachSingleUploadToModel($article, $request->cover_image);

            return $this->success('Successfully created the article!', 200, [
                'article' => $article,
//                'new_tags' => $newTags,
//                'cover_image' => $coverUrl
            ]);
        } catch (\Exception $exception) {
//            return $this->error($exception->getMessage(), 400);
            return $this->error('An error occurred while trying to create article.', 400);
        }
    }

    /**
     * Fetch Post
     *
     * Retrieve a specific blog post.
     *
     * @group Blog
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @urlParam article string required The ref attribute of the blog post. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Article $article): \Illuminate\Http\JsonResponse
    {
        $article->view();

        return $this->success('The article details', 200, [
            'article' => $article
        ]);
    }

    /**
     * Upload Post
     *
     * Update blog post.
     *
     * @group Blog
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam title string required The name of the blog post. Example: Amazing Spider-man
     * @bodyParam body string required The body of the blog post. Example: Some story goes here...
     * @bodyParam is_published boolean required Specify whether to publish or not. Example: true
     * @bodyParam tags string[] required Specify up to 5 tags for the post (1 minimum). Example: ["running", "charity"]
     * @bodyParam cover_image string required The cover image
     * @urlParam article string required The ref attribute of the blog post. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param UpdateArticleRequest $request
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateArticleRequest $request, Article $article): \Illuminate\Http\JsonResponse
    {
        try {
            $article->update($this->getCombinedRequest($request));

            $newTags = $this->getNewTags($request, $article);

            $this->attachSingleUploadToModel($article, $request->cover_image);

            return $this->success('Successfully updated the article!', 200, [
                'article' => $article->refresh(),
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update article.', 400);
        }
    }

    /**
     * Delete Post
     *
     * Delete blog post.
     *
     * @group Blog
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam article string required The ref attribute of the blog post. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Article $article): \Illuminate\Http\JsonResponse
    {
        try {
            self::deleteExistingFile($article->upload(), 'public');

            return $this->success('Successfully deleted the article');
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete article.', 400);
        }
    }

    /**
     * @param Request $request
     * @param $article
     * @return array
     */
    private function getNewTags(Request $request, $article): array
    {
        $newTags = [];

        foreach ($request->tags as $tag) {
            $tag = Tag::firstOrCreate([
                'name' => Str::of($tag)->title()->trim()
            ]);

            $article->tags()->syncWithoutDetaching([$tag->id]);

            if ($tag->wasRecentlyCreated) {
                $newTags[] = $tag;
            }
        }

        return $newTags;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getCombinedRequest(Request $request): array
    {
        return array_combine(
            $request->validated(),
            ['is_published' => $request->has('is_published')]
        );
    }

    /**
     * @param Article $article
     * @param UpdateArticleRequest $request
     * @return string|false|null
     * @throws \Exception
     */
    private function updateCoverImage(Article $article, UpdateArticleRequest $request): string|false|null
    {
        if (
            $article->upload()->exists()
            && Storage::disk(config('filesystems.default'))->exists($article->upload->url)
        ) {
            Storage::disk(config('filesystems.default'))->delete($article->upload->url);
        }

        $uploads = (new FileManager($article))->upload($request, 'image');

        if (isset($uploads) && is_array($uploads)) {
            return $uploads[0];
        }

        return null;
    }

    /**
     * @param string|null $search
     * @return \Closure
     */
    private function applySearchTermFilter(string|null $search): \Closure
    {
        return function ($query) use ($search) {
            if (isset($search)) {
                $query->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('body', 'LIKE', "%{$search}%");
            }
        };
    }

    /**
     * @param string|null $tag
     * @return \Closure
     */
    private function applyTagFilter(string|null $tag): \Closure
    {
        return function ($query) use($tag) {
            if (isset($tag)) {
                $query->whereHas('tags', function ($query) use ($tag) {
                    $query->where('name', $tag);
                });
            }
        };
    }
}
