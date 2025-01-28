<?php

namespace App\Repositories;

use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Validation\Rules\DataUri;

use App\Models\Faq;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Contracts\CanHaveManyFaqs;
use App\Contracts\FaqRepositoryContract;
use App\Models\FaqDetails;
use App\Services\FileManager\FileManager;
use App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException;
use App\Services\FileManager\Traits\UploadModelTrait;

class FaqRepository implements FaqRepositoryContract
{
    use UploadModelTrait;

    /**
     * @param CanHaveManyFaqs $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(CanHaveManyFaqs $model): \Illuminate\Database\Eloquent\Collection
    {
        return $model->faqs()->get();
    }

    /**
     * @param CanHaveManyFaqs $model
     * @param Faq $faq
     * @return Model|\Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function show(CanHaveManyFaqs $model, Faq $faq): Model|\Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $model
            ->faqs()
            ->where('ref', $faq->ref)
            ->firstOrFail();
    }

    /**
     * @throws \App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException
     * @throws \Exception
     */
    public function store(array $validated, CanHaveManyFaqs $model): array
    {
        $userId = request()->user()->id;
        $faqs = [];
        foreach ($validated['faqs'] as $faq) {
            $newFaq = $model->faqs()->create([
                'section' => $faq['section'],
                'description' => $faq['description'] ?? null,
                'user_id' => $userId
            ]);

            $this->processValidatedDetails($faq['details'], $newFaq);
            $faqs[] = $newFaq->fresh();
        }
        return $faqs;
    }

    /**
     * @param array $validated
     * @param CanHaveManyFaqs $model
     * @return array
     * @throws \App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException
     */
    public function update(array $validated, CanHaveManyFaqs $model): array
    {
        $faqs = [];
        foreach ($validated['faqs'] as $faq) {
            $formattedData['section'] = $faq['section'] ?? null;
            $formattedData['description'] = $faq['description'] ?? null;
            $formattedData = array_filter($formattedData);

            if (isset($faq['faq_id'])) {
                $newFaq = tap($model->faqs()
                    ->whereId($faq['faq_id'])
                    ->firstOrFail())
                    ->update($formattedData);
            } else {
                $newFaq = $model->faqs()
                    ->create($formattedData);
            }

            if (isset($faq['details'])) {
                $this->processValidatedDetails($faq['details'], $newFaq);
            }
            $faqs[] = $newFaq->fresh();
        }
        return $faqs;
    }

    /**
     * @param array $validated
     * @param CanHaveManyFaqs $model
     * @return bool|int|null
     */
    public function destroy(array $validated, CanHaveManyFaqs $model): bool|int|null
    {
        $faq = Faq::findOrFail($validated['faq_id']);

        $match = $model->faqs()
            ->where('ref', $faq->ref)
            ->firstOrFail();

        $paths = $match->uploads->pluck('url');
        return Storage::disk(config('filesystems.default'))->delete($paths->toArray());
    }

    /**
     * @param array $validated
     * @param CanHaveManyFaqs $model
     * @return bool
     */
    public function destroyManyFaqs(array $validated, CanHaveManyFaqs $model): CanHaveManyFaqs
    {
        $faqs = $model->faqs()->whereIn('id', $validated['faqs_ids'])->get();

        foreach ($faqs as $faq) {
            $faq->delete();
        }

        return $model;
    }

    /**
     * @param  array $validated
     * @param  Faq $faq
     * @return Faq
     */
    public function destroyManyFaqDetails(array $validated, Faq $faq): Faq
    {
        $faqDetails = $faq->faqDetails()->whereIn('id', $validated['faq_details_ids'])->get();

        foreach ($faqDetails as $faqDetail) {
            $faqDetail->delete();
        }

        return $faq;
    }
    
    /**
     * Remove Image
     *
     * @param  FaqDetails $faqDetails
     * @param  string $uploadRef
     * @return FaqDetails
     */
    public function removeImage(FaqDetails $faqDetails, string $uploadRef)
    {
        $this->detachUpload($faqDetail, $uploadRef);

        return $faqDetails->refresh();
    }
    
    /**
     * @param $details
     * @param $newFaq
     * @return void
     * @throws UnableToOpenFileFromUrlException
     */
    private function processValidatedDetails($details, $newFaq): void
    {
        foreach ($details as $detail) {
            $data = array_filter([
                'question' => $detail['question'] ?? null,
                'answer' => $detail['answer'] ?? null,
                'images' => $detail['images'] ?? []
            ]);
            if (isset($detail['details_id'])) {
                $newFaqDetails = tap($newFaq->faqDetails()
                    ->whereId($detail['details_id'])
                    ->firstOrFail())
                    ->update($data);
            } else {
                $newFaqDetails = $newFaq->faqDetails()
                    ->create($data);
            }

            if (isset($data['images']) && $data['images']) {
                $this->attachMultipleUploadsToModel($newFaqDetails, $data['images']);
            }
        }
    }
}
