<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyFaqRequest;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\Page;
use App\Repositories\FaqRepository;
use App\Traits\Response;
use Illuminate\Support\Facades\Log;

class PageFaqController extends Controller
{
    use Response;

    public function __construct(protected FaqRepository $faqRepository)
    {
        parent::__construct();
    }

    /**
     * Page FAQs
     *
     * FAQs associated with specified Page.
     *
     * @group PageFAQs
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam event string required The ref attribute of the Page. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param Page $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Page $page): \Illuminate\Http\JsonResponse
    {
        $faqs = $this->faqRepository->index($page);

        return $this->success('Page FAQs list', 200, [
            'faqs' => $faqs
        ]);
    }

    /**
     * Add new FAQs
     *
     * Create new FAQs associated with specified page.
     *
     * @group PageFAQs
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faqs[] string required The FAQs collection being created for specified page. Example: [["section": "some section 1", "description": "some description 1", "details": ["question": "some question 1", "answer": "some answer 1"], "images": ["data:image/png;base64,iVBORw0..."]]]
     * @urlParam page string required The ref attribute of the Page. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param StoreFaqRequest $request
     * @param Page $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreFaqRequest $request, Page $page): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $faqs = $this->faqRepository->store($validated, $page);

            return $this->success('Successfully created Page FAQs!', 201, [
                'faqs' => $faqs,
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create FAQs.', 400);
        }
    }

    /**
     * Update existing FAQs
     *
     * Update FAQs associated with specified page.
     *
     * @group PageFAQs
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faqs[] string required The FAQs collection being created for specified page. (The array items should include a key value pair of faq_id - id). Example: [["faq_id": 1, "section": "some section 1", "description": "some description 1", "details": ["question": "some question 1", "answer": "some answer 1"], "images": ["data:image/png;base64,iVBORw0..."]]]
     * @urlParam page string required The ref attribute of the Page. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param UpdateFaqRequest $request
     * @param Page $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFaqRequest $request, Page $page): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $faq = $this->faqRepository->update($validated, $page);

            return $this->success('Successfully updated the FAQ!', 200, [
                'faq' => $faq,
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update FAQ.', 400);
        }
    }

    /**
     * Delete an existing FAQ
     *
     * Delete a FAQ associated with specified page.
     *
     * @group PageFAQs
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faq_id integer required The id attribute of FAQ being deleted. Example: 1
     * @urlParam page string required The ref attribute of the Page. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param DestroyFaqRequest $request
     * @param Page $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DestroyFaqRequest $request, Page $page): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->faqRepository->destroy($validated, $page);

            return $this->success('Successfully deleted the FAQ!');
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->error('An error occurred while trying to delete FAQ.', 400);
        }
    }
}
