<?php

namespace App\Services\PasswordProtectionPolicy\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PasswordProtectionPolicy\Requests\StorePassphraseRequest;
use App\Services\PasswordProtectionPolicy\Requests\UpdatePassphraseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


class PassphraseController extends Controller
{
    use Response;

    /**
     * @var
     */
    protected $passphraseQuery;

    public function __construct()
    {
        $this->passphraseQuery = optional(request()->user())
            ->passphrase();
    }

    /**
     * allow user create a passphrase
     * @param StorePassphraseRequest $request
     * @return JsonResponse
     */
    public function store(StorePassphraseRequest $request)
    {
        $this->passphraseQuery
            ->create([
                'response' => $request->response
            ]);

        return $this->success([], 201, 'Your passphrase has been saved!');
    }

    /**
     * allow user update passphrase
     * @param UpdatePassphraseRequest $request
     * @return JsonResponse
     */
    public function update(UpdatePassphraseRequest $request)
    {
        $this->passphraseQuery
            ->first()
            ->update([
                'response' => $request->response
            ]);

        return $this->success([], 200, 'Your passphrase has been updated!');
    }

    /**
     * check is user has passphrase matching provided passphrase
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
       if ($this->hasSetUpPassphrase()) {
           if ($this->isValid($request)) {
               //reset counter
               $request->user()->cacheTempPassToken();
               return $this->success();
           }
           return $this->error('Invalid passphrase provided.', 423);
       }

        return $this->error('You need to first setup your passphrase.', 401);
    }

    /**
     * validate submitted passphrase
     * @param Request $request
     * @return array
     */
    protected function validatedResponse(Request $request): array
    {
        return $request->validate([
            'response' => [
                'required',
                'string',
                'exists:passphrases,response'
            ],
        ]);
    }

    /**
     * check if user provided passphrase is valid
     * @param Request $request
     * @return bool
     */
    protected function isValid(Request $request): bool
    {
        return $this->passphraseQuery
            ->where('response', $this->validatedResponse($request)['response'])
            ->exists();
    }

    /**
     * check if user has set up passphrase
     * @return bool
     */
    protected function hasSetUpPassphrase(): bool
    {
        return $this->passphraseQuery
            ->exists();
    }
}
