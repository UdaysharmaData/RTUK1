<?php

namespace App\Http\Controllers\Portal;

use Str;
use Validator;
use App\Traits\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Modules\Charity\Requests\DripRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Models\Drip;
use Illuminate\Database\Events\QueryExecuted;

/**
 * @group Settings
 * Manages the application's fundraising emails settings
 * @authenticated
 */
class DripController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Drip Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the application's fundraising emails settings.
    |
    */

    use Response;

    public function __construct()
    {
        parent::__construct();

        // $this->middleware('role:can_manage_drip_emails');
    }

    /**
     * Get fundraising emails (drip emails) settings
     * 
     * @queryParam page integer The page data to return Example: 1
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    // TODO: Rename this method name to fundraisingEmails after the model Drip model would have been renamed to Fundraising Model (after the api migrations)
    public function dripEmails(Request $request): JsonResponse
    {
        try {
            $dripEmails = Drip::paginate(10);

            foreach ($dripEmails as $drip) {
                $drip->schedule = $drip->schedule_days > 1 ? $drip->schedule_days.' days '.$drip->schedule_type.' the event' : $drip->schedule_days.' day '.$drip->schedule_type.' the event';   
            }
    
        } catch (ModelNotFoundException $e) {
            return $this->error('The drip emails were not found!', 406);
        }

        return $this->success('The drip emails!', 200, $dripEmails);
    }

    /**
     * Create a fundraising email setting
     * 
     * @param DripRequest $request
     * @return JsonResponse
     */
    public function create(DripRequest $request): JsonResponse
    {
        try {
            $fundraisingEmail = Drip::firstOrNew($request->all());
            $fundraisingEmail->save();
        } catch (QueryException $e) {
            return $this->error('Unable to create the fundraising email! Please try again', 406);
        }

        return $this->success('Successfully created the fundraising email!', 201, $fundraisingEmail);
    }

    /**
     * Get a fundraising email setting details
     * 
     * @urlParam id integer required The id of the fundraising email. Example: 3
     * @queryParam page integer The page data to return. Example:1
     * @return JsonResponse
     */
    public function dripEmail(int $id): JsonResponse
    {
        try {
            $fundraisingEmail = Drip::with(['charityDrips' => function($query) {
                    $query->paginate(2);
                }, 'charityDrips.charity.category'])->findOrFail($id);

            $fundraisingEmail->schedule = $fundraisingEmail->schedule_days > 1 ? $fundraisingEmail->schedule_days.' days '.$fundraisingEmail->schedule_type.' the event' : $fundraisingEmail->schedule_days.' day '.$fundraisingEmail->schedule_type.' the event';
            $fundraisingEmail->templateDesc = ucwords(Str::replace('-', ' ', $fundraisingEmail->template));
        } catch (ModelNotFoundException $e) {
            return $this->error('The fundraising email was not found!', 404);
        }

        return $this->success('The fundraising email details!', 200, $fundraisingEmail);
    }

    /**
     * Update a fundraising email setting
     * 
     * @param DripRequest $request
     * @urlParam id integer The id of the fundraising email. Example: 7
     * @return JsonResponse 
     */
    public function update(DripRequest $request, int $id): JsonResponse
    {
        try {
            $fundraisingEmail = Drip::findOrFail($id);
            try {
                $fundraisingEmail->fill($request->all());
                $fundraisingEmail->save();
            } catch (QueryException $e) {
                return $this->error('Unable to udpate the fundraising email! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The fundraising email was not found!', 404);
        }

        return $this->success('Successfully updated the fundraising email!', 200, $fundraisingEmail);
    }

    /**
     * Delete a fundraising email setting
     * 
     * @urlParam id integer required The id of the fundraising email setting
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $fundraisingEmail = Drip::findOrFail($id);
            try {
                $fundraisingEmail->delete();
            } catch (QueryException $e) {
                return $this->error('Unable to delete the fundraising email! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The fundraising email was not found!', 404);
        }

        return $this->success('Successfully deleted the fundraising email!', 200, );
    }

}
