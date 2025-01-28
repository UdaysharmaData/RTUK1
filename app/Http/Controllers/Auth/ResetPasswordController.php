<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\VerificationCode;
use App\Services\Auth\Rules\ValidCode\ValidCode;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use JetBrains\PhpStorm\ArrayShape;

class ResetPasswordController extends Controller
{
    /**
     * @var string
     */
    private string $email;

    /**
     * @var string
     */
    private string $code;

    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Reset Password
     *
     * Complete the password reset request initiated by the user.
     *
     * @group Authentication
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam email string required The email of the User. Example: user@email.com
     * @bodyParam token string required Token sent in response from requesting a reset code. Example: 357742ecb53be20ad70e4b0c233a2bcee289d8f5aa3e2f844c527019f5d7496
     * @bodyParam code string required Code received in mail after requesting reset code. Example: 759210
     * @bodyParam password string required Specifies user's proposed password. Example: newPASSword123@
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function reset(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $this->email = $request->email;
        $request->validate($this->rules(), $this->validationErrorMessages());
        $this->code = $request->code;

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
            $this->resetPassword($user, $password);
        }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
            ? $this->sendResetResponse($request, $response)
            : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    #[ArrayShape([
        'token' => "string",
        'email' => "string",
        'password' => "array",
        'code' => "\App\Services\Auth\Rules\ValidCode\ValidCode[]"
    ])] protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', Rules\Password::defaults()],
            'code' => [new ValidCode($this->email)]
        ];
    }

    /**
     * Reset the given user's password.
     *
     * @param CanResetPassword $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $this->setUserPassword($user, $password);

        $user->save();
        VerificationCode::where('code', $this->code)
            ->update(['is_active' => false]);

        event(new PasswordReset($user));
    }
}
