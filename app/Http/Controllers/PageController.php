<?php

namespace App\Http\Controllers;


use App\Http\Requests\CustomizeStorePageRequest;
use App\Http\Requests\UpdateCustomizePageRequest;
use App\Enums\RedirectStatusEnum;
use App\Models\Redirect;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\PageDataService;
use App\Services\DataServices\CustomizePageDataService;
use App\Services\DataServices\UserDataService;
use App\Services\SoftDeleteable\Exceptions\DeletionConfirmationRequiredException;
use App\Services\SoftDeleteable\Exceptions\InvalidSignatureForHardDeletionException;
use App\Services\SoftDeleteable\SoftDeleteableManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use App\Models\Faq;
use App\Models\Page;
use App\Models\CustomizePage;
use App\Traits\Response;
use App\Enums\ListTypeEnum;
use App\Enums\MetaRobotsEnum;
use App\Facades\ClientOptions;
use App\Repositories\FaqRepository;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Http\Requests\DeletePagesRequest;
use App\Http\Requests\RestorePagesRequest;
use App\Services\DefaultQueryParamService;
use App\Http\Requests\DeletePageFaqsRequest;
use App\Http\Requests\DeletePageFaqDetailsRequest;
use App\Http\Requests\PageListingQueryParamsRequest;
use App\Models\FaqDetails;
use App\Services\Analytics\Events\AnalyticsViewEvent;
use App\Services\DataServices\GlobalSearchDataService;
use App\Traits\DraftCustomValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    use Response, DraftCustomValidator;

    public function __construct(protected FaqRepository $faqRepository, protected PageDataService $pageDataService, protected CustomizePageDataService $CustomizePageDataService)
    {
        parent::__construct();
    }

    /**
     * Get Pages
     *
     * Get paginated list of pages.
     *
     * @group Pages
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Specifying a keyword similar to page name, url, meta title, or meta description. Example: https://somelink.test
     * @queryParam status string Specifying the inclusion of only pages with status attribute online/offline. Example: 1
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam meta_keywords string Specifying comma seperated values matching items in page's meta keywords attribute array. Example: keyword-1
     * @queryParam faqs string Specifying the inclusion of ONLY pages with associated FAQs. Example: with
     * @queryParam period string Filter by specifying a period. Example: 1h,6h,12h,24h,7d,30d,90d,180d,1y,All
     * @queryParam year string Filter by specifying a year. Example: 2022
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,url:asc,created_at:desc
     *
     * @param PageListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function index(PageListingQueryParamsRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $pages = (new CacheDataManager(
                $this->pageDataService->setLoadRedirect(true),
                'getPaginatedList',
                [$request]
            ))->getData();

            return $this->success('Pages List', 200, [
                'pages' => $pages,
                'options' => [
                    ...ClientOptions::only('general', ['period', 'faqs', 'order_direction', 'deleted', 'drafted']),
                    ...ClientOptions::only('pages', ['status', 'order_by', 'year']),
                ],
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Pages))->getDefaultQueryParams()
            ]);
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e);
            return $this->error('No result(s) found.', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching Pages.', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Pages.', 400);
        }
    }

    /**
     * Create a new Page
     *
     * New pages can be created with optional FAQs properties for pages that requires FAQs.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @param StorePageRequest $request
     * @return JsonResponse
     */
    public function store(StorePageRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $page = Page::create($validated = $request->validated());

            if (isset($validated['meta'])) {
                $page->addMeta($validated['meta']);
            }

            if (isset($validated['faqs'])) {
                $faqs = $this->faqRepository->store($validated, $page);
            }

            return $this->success('New Page Created.', 201, [
                'page' => $page->fresh()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while creating page.', 400);
        }
    }

    /**
     * Fetch Page Options
     *
     * Retrieve page creation options data.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Page options retrieved.', 200, [
            'options' => [
                ...ClientOptions::only('pages', ['status']),
                'robots' => MetaRobotsEnum::_options()
            ]
        ]);
    }

    /**
     * Fetch Page Options for Edit
     *
     * Retrieve page creation options data.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function edit(): JsonResponse
    {
        return $this->success('Page options retrieved.', 200, [
            'options' => [
                ...ClientOptions::only('pages', ['status']),
                'robots' => MetaRobotsEnum::_options()
            ]
        ]);
    }

    /**
     * Fetch Portal Page
     *
     * Retrieve page data matching specified ref attribute.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     * @header X-Platform-User-Identifier-Key RTHUB.v1.98591b54-db61-46d4-9d29-47a8a7f325a8.1675084780
     *
     * @urlParam ref string required The ref attribute of the page. Example: 97f62d3d-bf9d-42ac-88c4-9d56cd910d7a
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function show(string $ref): JsonResponse
    {
        try {
            $page = (new CacheDataManager(
                $this->pageDataService->setLoadRedirect(true),
                'edit',
                [$ref]
            ))->getData();

//            if (request()?->route()?->getName() === 'client.pages.show') {
//                AnalyticsViewEvent::dispatch($page);
//            }

            return $this->success('Page data retrieved.', 200, [
                'page' => $page,
                'options' => [
//                    ...ClientOptions::only('analytics', ['interaction_types']),
                    ...ClientOptions::only('pages', ['status']),
                    ...ClientOptions::only('general', ['faqs']),
                    'robots' => MetaRobotsEnum::_options()
                ]
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error('Page not found.', 404);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching page.', 400);
        }
    }

    /**
     * Fetch Client Page
     *
     * Retrieve page data matching specified ref attribute on the Client application.
     *
     * @group Pages
     * @unauthenticated
     * @header Content-Type application/json
     * @header X-Platform-User-Identifier-Key RTHUB.v1.98591b54-db61-46d4-9d29-47a8a7f325a8.1675084780
     *
     * @urlParam ref string required The ref attribute of the page. Example: 97f62d3d-bf9d-42ac-88c4-9d56cd910d7a
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function _show(string $ref): JsonResponse
    {
        try {
            $page = (new CacheDataManager(
                $this->pageDataService,
                'show',
                [$ref]
            ))->getData();

            AnalyticsViewEvent::dispatch($page);

            return $this->success('Page data retrieved.', 200, [
                'page' => $page,
//                'options' => [
//                    ...ClientOptions::only('analytics', ['interaction_types']),
//                    ...ClientOptions::only('pages', ['status'])
//                ]
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error('Page not found.', 404);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching page.', 400);
        }
    }

    /**
     * Update a Page
     *
     * An existing page can be modified, alongside their FAQs properties when necessary.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required The ref attribute of the page. Example: 97f62d3d-bf9d-42ac-88c4-9d56cd910d7a
     *
     * @param  UpdatePageRequest  $request
     * @param Page $page
     * @return JsonResponse
     */
    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        try {
            $validated = $request->validated();

            $page->update(array_filter([
                'name' => $validated['name'] ?? null,
                'url' => $validated['url'] ?? null,
                'status' => $validated['status'] ?? null
            ]));

            if (isset($validated['meta'])) {
                $page = $page->addMeta($validated['meta']);
            }

            if (isset($validated['faqs'])) {
                $faqs = $this->faqRepository->update($validated, $page);
            }

            CacheDataManager::flushAllCachedServiceListings($this->pageDataService);
            $page = $this->pageDataService->modelWithAppendedAnalyticsAttribute($page->fresh());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating page.', 400);
        }

        return $this->success('Page has been updated.', 201, [
            'page' => $page
        ]);
    }

    /**
     * Mark one or many Pages as Published
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required The list of ids associated with pages. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('pages'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            Page::whereIntegerInRaw('id', $request->ids)->onlyDrafted()->markAsPublished();

            CacheDataManager::flushAllCachedServiceListings($this->pageDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);

            return $this->success('Page(s) has been marked as published.');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating page.', 400);
        }
    }


    /**
     * Mark one or many Pages as Draft
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required The list of ids associated with pages. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request)
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('pages'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            Page::whereIntegerInRaw('id', $request->ids)->markAsDraft();

            CacheDataManager::flushAllCachedServiceListings($this->pageDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);

            return $this->success('Page(s) has been marked as draft.');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating page.', 400);
        }
    }

    /**
     * Delete Page
     *
     * Delete page data matching specified ref attribute.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required The ref attribute of the page. Example: 97f62d3d-bf9d-42ac-88c4-9d56cd910d7a
     * @queryParam permanently string Optionally specifying to force-delete model, instead of the default soft-delete. Example: 1
     *
     * @param Page $page
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(Page $page): JsonResponse
    {
        try {
            if ($force = (request('permanently') == 1)) {
                $page->forceDelete();
            } else $page->delete();

            return $this->success('Page has been '. ($force ? 'permanently ' : null) . 'deleted.', 200, [
                'pages' => (new CacheDataManager(
                    $this->pageDataService,
                    'getPaginatedList',
                    [request()],
                    true
                ))->getData(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting page.', 400);
        }
    }

    /**
     * Delete Many Pages
     *
     * Delete multiple pages data by specifying their ids.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam pages_ids string[] required The list of ids associated with pages. Example: [1,2]
     * @queryParam permanently string Optionally specifying to force-delete model, instead of the default soft-delete. Example: 1
     *
     * @param DeletePagesRequest $request
     * @return JsonResponse
     */
    public function destroyMany(DeletePagesRequest $request): JsonResponse
    {
        try {
            $force = (request('permanently') == 1);
            $response = (new SoftDeleteableManagementService(Page::class))
                ->delete($request->validated('pages_ids'), 'permanently');

            CacheDataManager::flushAllCachedServiceListings($this->pageDataService);

            return $this->success('Page(s) '. ($force ? 'permanently ' : null) . 'deleted.', 200, [
                'pages' => (new CacheDataManager(
                    $this->pageDataService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData(),
            ]);
        } catch (DeletionConfirmationRequiredException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode(), $exception->payload);
        } catch (InvalidSignatureForHardDeletionException $exception) {
            Log::error($exception);

            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified pages(s).', 400);
        }
    }

    /**
     * Restore Many Pages
     *
     * Restore multiple pages data by specifying their ids.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam pages_ids string[] required The list of ids associated with pages. Example: [1,2]
     *
     * @param RestorePagesRequest $request
     * @return JsonResponse
     */
    public function restoreMany(RestorePagesRequest $request): JsonResponse
    {
        try {
            $response = (new SoftDeleteableManagementService(Page::class))
                ->restore($request->validated('pages_ids'));

            CacheDataManager::flushAllCachedServiceListings($this->pageDataService);

            return $this->success('Specified page(s) has been restored.', 200, [
                'pages' => (new CacheDataManager(
                    $this->pageDataService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while restoring specified page(s).', 400);
        }
    }

    /**
     * Delete One/Many FAQs
     *
     * Delete multiple Page FAQs by specifying their ids.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faqs_ids string[] required The list of ids associated with specific page FAQs ids. Example: [1,2]
     *
     * @param DeletePageFaqsRequest $request
     * @param Page $page
     * @return JsonResponse
     */
    public function destroyManyFaqs(DeletePageFaqsRequest $request, Page $page): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqs($request->validated(), $page);

            CacheDataManager::flushAllCachedServiceListings($this->pageDataService);

            return $this->success('Page FAQ(s) has been deleted.', 200, [
                'page' => $page->fresh()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting specified page FAQ(s).', 400);
        }
    }

    /**
     * Delete One/Many FAQ Details
     *
     * Delete multiple Page FAQ details by specifying their ids.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faq_details_ids string[] required The list of ids associated with specific page faq_details ids. Example: [1,2]
     *
     * @param DeletePageFaqDetailsRequest $request
     * @param Page $page
     * @param Faq $faq
     * @return JsonResponse
     */
    public function destroyManyFaqDetails(DeletePageFaqDetailsRequest $request, Page $page, Faq $faq): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqDetails($request->validated(), $faq);

            CacheDataManager::flushAllCachedServiceListings($this->pageDataService);

            return $this->success('Page FAQ detail(s) has been deleted.', 200, [
                'page' => $page->fresh()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting specified page FAQ details.', 400);
        }
    }

     /**
     * Remove faq details image
     *
     * @param  Page $page
     * @param  Faq $faq
     * @param  FaqDetails $faqDetails
     * @param  string $upload_ref
     * @return JsonResponse
     */
    public function removeFaqDetailImage(Page $page, Faq $faq, FaqDetails $faqDetails, string $upload_ref): JsonResponse
    {
        try {
            $this->faqRepository->removeImage($faqDetails, $upload_ref);

            CacheDataManager::flushAllCachedServiceListings($this->pageDataService);

            return $this->success('Successfully removed the image!', 200, [
                'page' =>  $page->load(['faqs'])
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The image was not found!', 404);
        }
    }

    /**
     * Delete Meta
     *
     * Delete Page Meta.
     *
     * @group Pages
     * @authenticated
     * @header Content-Type application/json
     *
     * @param Page $page
     * @return JsonResponse
     */
    public function destroyMeta(Page $page): JsonResponse
    {
        try {
            $page = $page->deleteMeta();

            return $this->success('Page Meta has been deleted.', 200, [
                'page' => $page
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting Page Metadata.', 400);
        }
    }

    public function customizePages(PageListingQueryParamsRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $CustomizePages = $this->CustomizePageDataService->setLoadRedirect(true)->getPaginatedList($request);
            return $this->success('Customize Pages List', 200, [
                'pages' => $CustomizePages,
                'options' => [
                    ...ClientOptions::only('general', ['period', 'faqs', 'order_direction', 'deleted', 'drafted']),
                    ...ClientOptions::only('pages', ['status', 'order_by', 'year']),
                ],
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Pages))->getDefaultQueryParams()
            ]);
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e);
            return $this->error('No result(s) found.', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching Customize Pages.', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Customize Pages.', 400);
        }
    }

    public function customizePagesStore(CustomizeStorePageRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $page = CustomizePage::create($validated = $request->validated());

            if (isset($validated['meta'])) {
                $page->addMeta($validated['meta']);
            }

            if (isset($validated['faqs'])) {
                $faqs = $this->faqRepository->store($validated, $page);
            }

            CustomizePage::where('id', $page->id)->update([
                'chunks' => json_encode($request->chunks),
                'html_content' => $request->html_content
            ]);

            return $this->success('New Customize Page Created.', 201, [
                'page' => $page->fresh()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while creating Customize page.' .$exception->getMessage() , 400);
        }
    }

    public function customizePagesAdd(): JsonResponse
    {
        return $this->success('Customize Page options retrieved.', 200, [
            'options' => [
                ...ClientOptions::only('CustomizePages', ['status']),
                'robots' => MetaRobotsEnum::_options()
            ]
        ]);
    }

    public function customizePagesShow(string $ref): JsonResponse
    {
        try {
            $page = (new CacheDataManager(
                $this->CustomizePageDataService->setLoadRedirect(true),
                'edit',
                [$ref]
            ))->getData();

            return $this->success('Page data retrieved.', 200, [
                'page' => $page,
                'options' => [
//                    ...ClientOptions::only('analytics', ['interaction_types']),
                    ...ClientOptions::only('pages', ['status']),
                    ...ClientOptions::only('general', ['faqs']),
                    'robots' => MetaRobotsEnum::_options()
                ]
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error('Page not found.', 404);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching page.', 400);
        }
    }
    public function customizePagesEdit(string $ref): JsonResponse
    {
        try {
            $page = (new CacheDataManager(
                $this->CustomizePageDataService->setLoadRedirect(true),
                'edit',
                [$ref]
            ))->getData();

            return $this->success('Customize Page data retrieved.', 200, [
                'page' => $page,
                'options' => [
                    ...ClientOptions::only('Customizepages', ['status']),
                    ...ClientOptions::only('general', ['faqs']),
                    'robots' => MetaRobotsEnum::_options()
                ]
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error('Customize Page not found.', 404);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Customize page.', 400);
        }
    }

    public function customizePagesUpdate(UpdateCustomizePageRequest $request): JsonResponse
    {
        CustomizePage::where('ref', $request->ref)->forceDelete();
        try {
            $page = CustomizePage::create($validated = $request->validated());

            if (isset($validated['meta'])) {
                $page->addMeta($validated['meta']);
            }

            if (isset($validated['faqs'])) {
                $faqs = $this->faqRepository->store($validated, $page);
            }

            CustomizePage::where('id', $page->id)->update([
                'chunks' => json_encode($request->chunks),
                'html_content' => $request->html_content
            ]);

            CacheDataManager::flushAllCachedServiceListings($this->CustomizePageDataService);

            return $this->success('Customize Page Updated.', 201, [
                'page' => $page->fresh()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while Updated Customize page.' .$exception->getMessage() , 400);
        }
    }

    public function customizePagesDestroy(Request $request): JsonResponse
    {
        try {
            $customize_page = CustomizePage::where('ref', $request->ref)->delete();
            return $this->success('New Customize Page Deleted.', 201, []);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting Customize page.', 400);
        }
    }

    public function getMenusShow(): JsonResponse
    {
        try {
            $dataMenus = CustomizePage::select('id', 'name', 'slug', 'ref')->get()->makeHidden(['faqs', 'meta']);
            return $this->success('Menus List data retrieved', 200, $dataMenus);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error('Menus not found.', 404);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Menus.', 400);
        }
    }

    public function getDetailsByMenus(Request $request): JsonResponse
    {
        try {
            $dataMenusByDetails = CustomizePage::where('ref', $request->ref)->get();
            return $this->success('Menus Details List data retrieved', 200, $dataMenusByDetails);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error('Menus Details not found.', 404);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Menus Details.', 400);
        }
    }
}
