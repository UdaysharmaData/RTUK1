<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\Exceptions\UnsupportedVerificationService;
use App\Services\Auth\Traits\VerifiesAttribute;


class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesAttribute;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Specify user account attribute being verified.
     *
     * @var string
     */
    protected string $attributeName;

    /**
     * Create a new controller instance.
     *
     * @return void
     * @throws UnsupportedVerificationService
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:6,1')->only('verify', 'resend');

        $this->checkVerificationType();
    }

    /**
     * @param string $value
     * @return void
     */
    protected function setAttributeName(string $value): void
    {
        $this->attributeName = $value;
    }

    /**
     * @return void
     * @throws UnsupportedVerificationService
     */
    private function checkVerificationType(): void
    {
        if (request()->has('type')) {
            $type = strtolower(trim(request('type')));

            switch ($type) {
                case 'phone':
                case 'email':
                    $this->setAttributeName($type);
                    break;
                default:
                    throw new UnsupportedVerificationService("[$type] verification service is not currently supported.");
            }
        } else throw new UnsupportedVerificationService("Verification parameter [type] not specified.");
    }
}
