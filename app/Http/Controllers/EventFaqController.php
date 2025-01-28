<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyFaqRequest;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Modules\Event\Models\Event;
use App\Repositories\FaqRepository;
use App\Traits\Response;

class EventFaqController extends Controller
{
    use Response;

    public function __construct(protected FaqRepository $faqRepository)
    {
        parent::__construct();
    }

    /**
     * Event FAQs
     *
     * FAQs associated with specified event.
     *
     * @group EventFAQs
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam event string required The ref attribute of the Event. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Event $event): \Illuminate\Http\JsonResponse
    {
        $faqs = $this->faqRepository->index($event);

        return $this->success('Event FAQs list', 200, [
            'faqs' => $faqs
        ]);
    }

    /**
     * Add new FAQs
     *
     * Create new FAQs associated with specified event.
     *
     * @group EventFAQs
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faqs[] string required The FAQs collection being created for specified event. Example: [["section": "some section 1", "description": "some description 1", "details": ["question": "some question 1", "answer": "some answer 1"], "images": ["data:image/png;base64,iVBORw0..."]]]
     * @urlParam event string required The ref attribute of the Event. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param StoreFaqRequest $request
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreFaqRequest $request, Event $event): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $faqs = $this->faqRepository->store($validated, $event);

            return $this->success('Successfully created Event FAQs!', 201, [
                'faqs' => $faqs,
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create FAQs.', 400);
        }
    }

    /**
     * Update existing FAQs
     *
     * Update FAQs associated with specified event.
     *
     * @group EventFAQs
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faqs[] string required The FAQs collection being created for specified event. (The array items should include a key value pair of faq_id - id). Example: [["faq_id": 1, "section": "some section 1", "description": "some description 1", "details": ["question": "some question 1", "answer": "some answer 1"], "images": ["data:image/png;base64,iVBORw0..."]]]
     * @urlParam event string required The ref attribute of the Event. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param UpdateFaqRequest $request
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFaqRequest $request, Event $event): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $faq = $this->faqRepository->update($validated, $event);

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
     * Delete a FAQ associated with specified event.
     *
     * @group EventFAQs
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faq_id integer required The id attribute of FAQ being deleted. Example: 1
     * @urlParam event string required The ref attribute of the Event. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param DestroyFaqRequest $request
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DestroyFaqRequest $request, Event $event): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->faqRepository->destroy($validated, $event);

            return $this->success('Successfully deleted the FAQ!');
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete FAQ.', 400);
        }
    }
}
