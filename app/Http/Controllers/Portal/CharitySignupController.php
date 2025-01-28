<?php

namespace App\Http\Controllers\Portal;

use Excel;
use App\Traits\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Modules\Charity\Requests\CharitySignupRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Models\CharitySignup;
use App\Exports\CharitySignupCsvExport;

use App\Traits\DownloadTrait;

/**
 * @group Charity Signups (Enquiries)
 * Manages charity signups (enquiries) on the application
 * @authenticated
 */
class CharitySignupController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Charity Signup Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with charity signups (enquiries). That is
    | the creation, view, update, delete and more ...
    |
    */

    use Response, DownloadTrait;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // $this->middleware('role:can_manage_charity_enquiries');
    }

    /**
     * The list of charity signups
     * 
     * @queryParam category string Filter by category. No-example
     * @queryParam term string Filter by term. No-example
     * @queryParam page integer The page data to return Example: 1
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function signups(Request $request): JsonResponse
    {
        $enquiries = CharitySignup::query();

        if ($request->filled('category')) {
            $enquiries = $enquiries->where('sector', $request->category);
        }

        if ($request->filled('term')) {
            $enquiries = $enquiries->where('name', 'LIKE', '%'.$request->term.'%');
        }

        $enquiries = $enquiries->orderBy('created_at', 'desc')->paginate(10);

        return $this->success('The list of charity signups (enquiries).', 200, $enquiries);
    }

    /**
     * Create a charity signup (enquiry)
     * 
     * @param CharitySignupRequest $request
     * @return JsonResponse
     */
    public function create(CharitySignupRequest $request): JsonResponse
    {
        try {
           $enquiry = CharitySignup::firstOrCreate($request->all());
        } catch (QueryException $e) {
            return $this->error('Unable to create the charity enquiry! Please try again', 406);
        }

        return $this->success('Successfully created the charity enquiry!', 201, $enquiry);
    }

    /**
     * Update a charity signup (enquiry)
     * 
     * @param CharitySignupRequest $request
     * @urlParam id integer required The id of the charity signup. Example: 1
     * @return JsonResponse
     */
    public function update(CharitySignupRequest $request, int $id): JsonResponse
    {
        try {
            $enquiry = CharitySignup::findOrFail($id);
            try {
                $enquiry->update($request->all());
            } catch(QueryException $e) {
                return $this->error('Unable to update the charity enquiry! Please try again.', 406);
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The charity enquiry was not found!', 404);
        }

        return $this->success('Successfully updated the charity enquiry!');
    }

    /**
     * Delete a charity signup (enquiry)
     * 
     * @urlParam id integer required The id of the charity signup. Example: 1
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $enquiry = CharitySignup::findOrFail($id);

            try {
                $enquiry->delete();
            } catch(QueryException $e) {
                return $this->error('Unable to delete the charity enquiry! Please try again.', 406);
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The charity enquiry was not found!', 404);
        }

        return $this->success('Successfully deleted the charity enquiry', 200, $enquiry);
    }

    /**
     * Export charity signups (enquiry)
     * 
     * @queryParam category string Filter by category. No-example
     * @queryParam term string Filter by term. No-example
     * 
     * @param  Request  $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(Request $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        $enquiries = CharitySignup::select('name', 'sector as category', 'number as registration_number', 'website', 
            'contact_name', 'contact_email', 'contact_phone', 'address_1', 'address_2', 'city', 'postcode', 'created_at');

        if ($request->filled('category')) {
            $enquiries = $enquiries->where('sector', $request->category);
        }

        if ($request->filled('term')) {
            $enquiries = $enquiries->where('name', 'LIKE', '%'.$request->term.'%');
        }

        $enquiries = $enquiries->orderBy('created_at', 'desc')->get();
        
        if (!$enquiries) {
            return $this->error('Charity Signup was not found!', 406);
        }

        foreach ($enquiries as &$enquiry) {
            \Arr::forget($enquiry, 'created_at');
        }
        
        $headers = [
            'Content-Type' => 'text/csv',
        ];
        
        $fileName = 'Charity Enquiries - ' . date('Y-m-d H:i:s') . '.csv';
        Excel::store(new CharitySignupCsvExport($enquiries), $fileName, 'csvs', \Maatwebsite\Excel\Excel::CSV, $headers);
        $path = config('app.csvs_path'). '/'. $fileName;

        return static::_download($path, true);
    }
}
