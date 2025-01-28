<?php

namespace App\Http\Controllers\Portal;

use App\Enums\MetaRobotsEnum;
use DB;
use Rule;
use Storage;
use Validator;
use Illuminate\Http\Request;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Modules\Charity\Resources\CharityCategoryResource;
use App\Modules\Charity\Models\CharityCategory;
use App\Services\FileManager\Traits\UploadModelTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Traits\Response;
use App\Traits\HelperTrait;
use App\Traits\UploadTrait;
use App\Traits\ImageValidator;
use App\Traits\GalleryValidator;

/**
 * @group Charity Categories
 * Manages charity categories on the application
 * @authenticated
 */
class CharityCategoryController extends Controller
{
    use Response, HelperTrait, UploadTrait, UploadModelTrait, ImageValidator, GalleryValidator;

    /*
    |--------------------------------------------------------------------------
    | Charity Category Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with charity categories. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('role:can_manage_categories', [
            'except' => [
                'all',
                '_index',
                'charities'
            ]
        ]); // TODO: Change this permission to can_manage_charity_categories
    }

    /**
     * Paginated charity categories for dropdown fields.
     *
     * @queryParam page integer The page data to return Example: 1 
     * @queryParam per_page integer Items per page No-example
     */
    public function all(Request $request): JsonResponse
    {
        $categories = CharityCategory::query();

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $categories = $categories->paginate($perPage);

        return $this->success('All charity categories', 200, new CharityCategoryResource($categories));
    }

    /**
     * The list of charity categories
     * 
     * @queryParam status bool Filter by status. Example 1
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $categories = CharityCategory::withCount('charities');

        if ($request->filled('status')) {
            $categories = $categories->where('status', $request->status);
        }

        if ($request->filled('term')) {
            $categories = $categories->where('name', 'LIKE', '%' . $request->term . '%');
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $categories = $categories->paginate($perPage);

        return $this->success('The list of categories', 200, new CharityCategoryResource($categories));
    }

    /**
     * The list of charity categories
     * 
     * @group Charity Categories - Client
     * @unauthenticated
     * 
     * @queryParam status bool Filter by status. Example 1
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function _index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $categories = CharityCategory::with(['uploads'])
            ->withCount('charities');

        if ($request->filled('status')) {
            $categories = $categories->where('status', $request->status);
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $categories = $categories->paginate($perPage);

        return $this->success('The list of categories', 200, new CharityCategoryResource($categories));
    }

    /**
     * Get the charities under a category
     * 
     * @group Charity Categories - Client
     * @unauthenticated
     *
     * @urlParam category_slug string required The slug of the charity category. Example: cancer-children-youth
     * @return JsonResponse
     */
    public function charities(CharityCategory $category): JsonResponse
    {
        try {
            $category = CharityCategory::with(['charities', 'meta', 'uploads'])
                ->withCount('charities')
                ->where('ref', $category->ref)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->error('The charity category was not found!', 404);
        }

        return $this->success('The charity category details', 200, new CharityCategoryResource($category));
    }

    /**
     * Create a new charity category
     * 
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Create a charity category', 200, [
            'robots' => MetaRobotsEnum::_options(),
        ]);
    }

    /**
     * Store the new charity category
     * 
     * TODO: Scribe considers/submits true & false (boolean values) as strings whenever a file upload is part of the request. Please look deeper into this later and fix the issue on this request.
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The name of the charity category. Example: Cancer - Children & Youth 237
            'name' => ['required', 'string', 'unique:charity_categories,name'],
            // The status of the charity category. Example: 1
            'status' => ['required', 'boolean'],
            // The color. Example: #f0ad00
            'color' => ['required', 'string', 'max:16'],
            // The image.
            ...$this->imageRules(),
            ...$this->galleryRules(),
            'meta' => ['sometimes', 'required', 'array:title,description,keywords'],
            'meta.title' => [
                'string',
                Rule::requiredIf($request->meta == true)
            ],
            'meta.description' => [
                'string',
                Rule::requiredIf($request->meta == true)
            ],
            'meta.keywords' => [
                'string',
                Rule::requiredIf($request->meta == true)
            ]
        ], [
            ...$this->imageMessages(),
            ...$this->galleryMessages()
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $category = CharityCategory::create([
                'name' => $request->name,
                'status' => $request->status
            ], [
                'color' => $request->color
            ]);

            if ($request->filled('image')) { // Save the image
                $this->attachSingleUploadToModel($category, $request->image);
            }

            if ($request->filled('gallery')) {
                $this->attachMultipleUploadsToModel($category, $request->gallery);
            }

            if ($request->file('image') && $request->file('image')->isValid()) { // Save the charity category's image
                $path = config('app.images_path');

                if ($fileName = $this->moveUploadedFile($request->file('image'), $path, UploadUseAsEnum::Image)) {
                    $category->upload()->create([
                        'url' => $path . $fileName,
                        'title' => $category->name,
                        'type' => UploadTypeEnum::Image,
                        'use_as' => UploadUseAsEnum::Image
                    ]);
                }
            }

            $this->saveMetaData($request, $category); // Save meta data

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to create the charity category! Please try again', 406, $e->getMessage());
        } catch (FileException $e) {

            return $this->error('Unable to create the charity category! Please try again', 406, $e->getMessage());
        }
        return $this->success('Successfully created the charity category!', 200, new CharityCategoryResource($category->load(['uploads', 'meta'])));
    }

    /**
     * Get a charity category
     *
     * @urlParam id int required The id of the charity category. Example: 73
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = CharityCategory::with(['uploads', 'meta'])
                ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->error('The charity category was not found!', 404);
        }

        return $this->success('The charity category details', 200, new CharityCategoryResource($category));
    }

    /**
     * Edit a charity category
     *
     * @urlParam id int required The id of the charity category. Example: 73
     * @return JsonResponse
     */
    public function edit(int $id): JsonResponse
    {
        try {
            $category = CharityCategory::findOrFail($id);
        } catch (ModelNotFoundException $e) {

            return $this->error('The charity category was not found!', 404);
        }

        return $this->success('Edit the charity category', 200, [
            'charity' => new CharityCategoryResource($category),
            'robots' => MetaRobotsEnum::_options()
        ]);
    }

    /**
     * Update a charity category
     *
     *  TODO: Scribe considers/submits true & false (boolean values) as strings whenever a file upload is part of the request. Please look deeper into this later and fix the issue on this request.
     *
     * @param  Request  $request
     * @urlParam id int required The id of the charity category. Example: 73
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The name of the charity category. Example: Cancer - Children & Youth 237
            'name' => ['required', 'unique:charity_categories,name,' . $id],
            // status bool required The status of the charity category. Example: true
            'status' => ['required', 'boolean'],
            // The color. Example: #f0ad00
            'color' => ['sometimes', 'required', 'string', 'max:16'],
            // The image
            ...$this->imageRules(),
            ...$this->galleryRules(),
            'meta' => ['sometimes', 'required', 'array:title,description,keywords'],
            'meta.title' => [
                'string',
                Rule::requiredIf($request->meta == true)
            ],
            'meta.description' => [
                'string',
                Rule::requiredIf($request->meta == true)
            ],
            'meta.keywords' => [
                'string',
                Rule::requiredIf($request->meta == true)
            ]
        ], [
            ...$this->imageMessages(),
            ...$this->galleryMessages()
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $category = CharityCategory::with('meta')
                ->findOrFail($id);

            try {
                DB::beginTransaction();

                $category->update($request->all());

                if ($request->filled('image')) { // Save the image
                    $this->attachSingleUploadToModel($category, $request->image);
                }

                if ($request->filled('gallery')) {
                    $this->attachMultipleUploadsToModel($category, $request->gallery);
                }

                $this->saveMetaData($request, $category); // Save meta data

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to update the charity category! Please try again.', 406);
            } catch (FileException $e) {
                return $this->error('Unable to update the charity category! Please try again', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The charity category was not found!', 404);
        }

        return $this->success('Successfully updated the charity category', 200, new CharityCategoryResource($category->load(['uploads', 'meta'])));
    }

    /**
     * Delete a charity category
     * 
     * @urlParam id int required The id of the charity category. Example: 73
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = CharityCategory::findOrFail($id);

            try {
                DB::beginTransaction();

                if ($category->upload && Storage::disk(config('filesystems.default'))->exists($category->upload->url)) { // Delete the existing image if it exists
                    Storage::disk(config('filesystems.default'))->delete($category->upload->url);
                }

                $category->upload()?->delete();

                $category->delete();

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to delete the charity category! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The charity category was not found!', 404);
        }

        return $this->success('Successfully deleted the charity category', 200, new CharityCategoryResource($category));
    }
}
