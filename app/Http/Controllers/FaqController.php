<?php

namespace App\Http\Controllers;

use App\Enums\FaqCategoryNameEnum;
use App\Enums\FaqCategoryTypeEnum;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Page;
use App\Services\FileManager\FileManager;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FaqController extends Controller
{
    use Response;

    /**
     * FAQs Page
     *
     * API to handel https://runthrough.runthroughhub.com/faqs.
     *
     * @group FAQs
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        // general faqs
        $faqs = Cache::remember(
            $this->getPlatformRequestCacheKey(),
            now()->addHour(),
            function () {
                return Faq::query()
                    ->whereHasMorph(
                        'faqsable',
                        Page::class,
                        function (Builder $query) {
                            $query->where('path', '=','/faqs');
                        })->get();
            }
        );

        return $this->success('The list of faqs', 200, [
            'faqs' => $faqs
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreFaqRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(StoreFaqRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $this->getValidatedData($request);

            $category = FaqCategory::firstOrCreate([
                'name' => $validated['category'],
                'type' => $validated['type']
            ], []);

            $faq = $category->faqs()->create($request->only(['question', 'answer']));

            $urls = $this->getUploadedFilesUrl($faq, $request);

            return $this->success('Successfully created the faq!', 201, [
                'faq' => $faq,
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create FAQ.', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function show(Faq $faq)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function edit(Faq $faq)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFaqRequest  $request
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFaqRequest $request, Faq $faq): \Illuminate\Http\JsonResponse
    {
        try {
            $faq->update($request->toArray());

            $urls = $this->getUploadedFilesUrl($faq, $request); // todo: may need a re-write

            return $this->success('Successfully updated the faq!', 200, [
                'faq' => $faq,
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update FAQ.', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Faq $faq): \Illuminate\Http\JsonResponse
    {
        try {
            $faq->uploads->each(function ($upload) { // todo: extract to file manager
                Storage::disk(config('filesystems.default'))->exists($upload->url) && Storage::disk(config('filesystems.default'))->delete($upload->url);

                $upload->delete(); // check later: kinda redundant [$faq deletion cascading in effect]
            });

            $faq->delete();

            return $this->success('Successfully deleted the faq!', 200);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete FAQ.', 400);
        }
    }

    /**
     * @param $faq
     * @param Request $request
     * @return null|array
     * @throws \Exception
     */
    private function getUploadedFilesUrl($faq, Request $request): null|array
    {
        $uploads = (new FileManager($faq))->upload($request, 'images');

        if (isset($uploads) && is_array($uploads)) {
            $uploadUrls = $uploads;
        } else $uploadUrls = [];

        return $uploadUrls;
    }

    /**
     * @param StoreFaqRequest $request
     * @return array
     */
    private function getValidatedData(StoreFaqRequest $request): array
    {
        return $request->validate(array_merge([
            'category' => ['required', 'string', 'max:100'],
            'type' => [
                'required',
                'string',
                Rule::in(FaqCategoryNameEnum::cases())
            ]
        ], Faq::RULES['create_or_update']));
    }

    /**
     * @param FaqCategoryTypeEnum|string|null $type
     * @return FaqCategoryTypeEnum|string|null
     */
    private function getType(FaqCategoryTypeEnum|string|null $type): string|null|FaqCategoryTypeEnum
    {
        if ($type instanceof FaqCategoryTypeEnum) {
            $type = $type->value;
        }
        return $type;
    }

    /**
     * @return string
     */
    private function getPlatformRequestCacheKey(): string
    {
        $uri = request()->getUri();
        $site = clientSiteId();
        return sha1("$site+$uri");
    }
}
