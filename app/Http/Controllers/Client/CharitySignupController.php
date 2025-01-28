<?php

namespace App\Http\Controllers\Client;

// use DB;
use App\Mail\Mail;
use App\Traits\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Modules\Charity\Requests\CharitySignupRequest;

use App\Models\CharitySignup;
use App\Mail\CharitySignupMail;

/**
 * @group  Website - Charity Signups (Enquiries)
 * Manages charity signups (enquiries) from websites
 */
class CharitySignupController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Charity Signup Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles charity signup create
    |
    */

    use Response;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
            // DB::beginTransaction(); // Reason: Charity Signup is very important. Instead of rolling back on email send failure, 
                                      // we should save the error in our error_log file and report the issue to the admins and the developers so that they handle it from their ends
            $enquiry = CharitySignup::firstOrCreate($request->all());
            try {
                Mail::site()->to(config('mail.admin_email.address'), config('mail.admin_email.name'))->send(new CharitySignupMail($enquiry));
                // DB::commit();
            } catch (\Exception $e) {
                // TODO: Save the error in the error_log file.
                //       Report the issue to the admins and developers (via email or internal notification)`
                return $this->error('Successfully created the charity enquiry! ', 200);
            }
        } catch (QueryException $e) {
            // DB::rollback();
            return $this->error('Unable to create the charity enquiry! Please try again', 406);
        }

        return $this->success('Successfully created the charity enquiry!', 201, $enquiry);
    }
}
