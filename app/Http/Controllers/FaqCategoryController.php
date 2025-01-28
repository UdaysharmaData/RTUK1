<?php

namespace App\Http\Controllers;

use App\Enums\FaqCategoryNameEnum;
use App\Http\Requests\StoreFaqCategoryRequest;
use App\Http\Requests\UpdateFaqCategoryRequest;
use App\Models\FaqCategory;
use App\Traits\Response;

class FaqCategoryController extends Controller
{
    use Response;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $categories = FaqCategory::query()
            ->groupBy('type')
            ->latest()
            ->get();

        return $this->success('The list of faq categories', 200, [
            'categories' => $categories
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(): \Illuminate\Http\JsonResponse
    {
        $types = FaqCategoryNameEnum::cases();

        return $this->success('Create an faq category!', 200, [
            'types' => $types
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreFaqCategoryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreFaqCategoryRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $category = FaqCategory::create($request->toArray());

            return $this->success('Successfully created the faq category!', 201, [
                'category' => $category
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create category.', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FaqCategory  $faqCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(FaqCategory $faqCategory): \Illuminate\Http\JsonResponse
    {
        return $this->success('The faq category details', 200, [
            'faq_category' => $faqCategory
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\FaqCategory  $faqCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(FaqCategory $faqCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFaqCategoryRequest  $request
     * @param  \App\Models\FaqCategory  $faqCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateFaqCategoryRequest $request, FaqCategory $faqCategory): \Illuminate\Http\JsonResponse
    {
        try {
            $faqCategory->update($request->toArray());

            return $this->success('Successfully updated the faq category!', 200, [
                'category' => $faqCategory
            ], 201, 'Category has been updated!');
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update category', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FaqCategory  $faqCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(FaqCategory $faqCategory): \Illuminate\Http\JsonResponse
    {
        try {
            $faqCategory->delete();

            return $this->success('Successfully destroyed the faq category!', 200);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete category.', 400);
        }
    }
}
