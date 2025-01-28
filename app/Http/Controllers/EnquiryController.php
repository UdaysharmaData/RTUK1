<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\City;
use App\Models\ClientEnquiry;
use App\Models\Region;
use App\Models\Venue;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Partner\Models\Partner;
use App\Modules\Setting\Models\Site;
use App\Services\ClientOptions\EnquirySettings;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnquiryController extends Controller
{
    use Response;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $enquiries = ClientEnquiry::query()
            ->groupBy('enquiry_type')
            ->latest()
            ->get();

        return $this->success('The list of enquiries', 200, [
            'enquiries' => $enquiries
        ]);
    }

    /**
     * Enquiry Options
     *
     * Fetch Enquiry Options.
     *
     * @group Enquiries
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @param EnquirySettings $types
     * @return JsonResponse
     */
    public function create(EnquirySettings $types): \Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('Create an enquiry', 200, [
                'enquiry_types' => $types->options()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while retrieving enquiry category options.', 400);
        }
    }

    /**
     * Contact Us
     *
     * Handle an enquiry posting from the API client.
     *
     * @group Enquiries
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam full_name string required The full name of the user. Example: Wendy Bird
     * @bodyParam email string required The email of the User. Example: user@email.com
     * @bodyParam message string required The message of the enquiry. Example: I need to make an enquiry...
     * @bodyParam enquiry_type string required The type/category of enquiry selected. Example: race_entries_north
     *
     * @param  \App\Http\Requests\StoreEnquiryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEnquiryRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $enquiry = ClientEnquiry::create($request->validated());

            return $this->success('Successfully created an enquiry!', 201, [
                'enquiry' => $enquiry
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('We had some issues sending your enquiry. Please try again shortly.', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClientEnquiry  $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ClientEnquiry $message): \Illuminate\Http\JsonResponse
    {
        return $this->success('The enquiry details', 200, [
            'message' => $message
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClientEnquiry  $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ClientEnquiry $message): \Illuminate\Http\JsonResponse
    {
        try {
            $message->delete();

            return $this->success('Successfully deleted the enquiry!', 200);

        } catch (\Exception $exception) {

            return $this->error('An error occurred while trying to delete enquiry.', 400);
        }
    }

    public function validatePath($path)
    {
        $site_id = clientSiteId();
        $site = Site::where('id', $site_id)->firstOrFail();
        try {
            $type = $this->getTypeIteration($path);
            $segments = Str::of($path)->trim('/')->explode('/');
            $slug = $segments->last();
            if (is_null($slug)) {
                $slug = $path;
            }
            $combinationsQuery = DB::table('combinations')->select('id', 'slug', 'updated_at', 'path', 'created_at')
                ->where('site_id', $site->id)->where('path', '/' . $path)->whereNull('deleted_at');

            switch ($type) {
                case 'event':
                    $dataQuery = Event::select('id', 'slug', 'updated_at', 'created_at')
                        ->where('slug', $slug)->partnerEvent(Event::ACTIVE)->whereHas('eventCategories', function ($query) use ($site) {
                            $query->where('site_id', $site->id);});
                    return $this->checkDataExistence($dataQuery, $combinationsQuery);

                case 'regions':
                    $dataQuery = Region::select('id', 'slug', 'updated_at', 'created_at')
                        ->where('site_id', $site->id)->where('slug', $slug);
                    return $this->checkDataExistence($dataQuery, $combinationsQuery);

                case 'distances':
                    $dataQuery = EventCategory::select('id', 'slug', 'updated_at', 'created_at')
                        ->where('site_id', $site->id)->where('slug', $slug);
                    return $this->checkDataExistence($dataQuery, $combinationsQuery);

                case 'partners':
                    $dataQuery = Partner::select('id', 'slug', 'updated_at', 'created_at')
                        ->where('site_id', $site->id)->where('slug', $slug);
                    return $this->checkDataExistence($dataQuery, $combinationsQuery);

                case 'cities':
                    $dataQuery = City::select('id', 'slug', 'updated_at', 'created_at')
                        ->where('site_id', $site->id)->where('slug', $slug);
                    return $this->checkDataExistence($dataQuery, $combinationsQuery);

                case 'venues':
                    $dataQuery = Venue::select('id', 'slug', 'updated_at', 'created_at')
                        ->where('site_id', $site->id)->where('slug', $slug);
                    return $this->checkDataExistence($dataQuery, $combinationsQuery);

                case 'combination':
                    if ($combinationsQuery->exists()) {
                        return response()->json(['message' => 'Data found'], 200);
                    } else {
                        return response()->json(['message' => 'Data not found'], 404);
                    }

                default:
                    throw new \Exception("Unknown type: $type");
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function getTypeIteration($path)
    {
        $types = ['event', 'regions', 'distances', 'partners', 'cities', 'venues'];
        $matchedType = array_filter($types, fn($type) => Str::startsWith($path, $type));
        return !empty($matchedType) ? reset($matchedType) : 'combination';
    }

    public function checkDataExistence($dataQuery, $combinationsQuery)
    {
        if ($dataQuery->exists() || $combinationsQuery->exists()) {
            return response()->json(['message' => 'Data found'], 200);
        } else {
            return response()->json(['message' => 'Data not found'], 404);
        }
    }
}
