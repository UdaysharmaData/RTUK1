<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiClientHasInvalidTokenException;
use App\Exceptions\ApiClientHasNoTokensException;
use App\Exceptions\ApiClientTokenExpiredException;
use App\Exceptions\ApiClientTokenRevokedException;
use App\Models\ApiClient;
use App\Models\ApiClientToken;
use App\Traits\Response;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ApiRequestAuthorization extends Controller
{
    use Response;

    /**
     * @var Request
     */
    private Request $request;
    /**
     * @var ApiClient
     */
    private ApiClient $client;
    /**
     * @var ApiClientToken
     */
    private ApiClientToken $token;

    /**
     * @var HasMany
     */
    private HasMany $tokensQuery;

    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->request = $request;

        return $this->processClientAuthorizationRequest();
    }

    /**
     * update client state
     * @param ApiClient $newValue
     * @return void
     */
    private function setClient(ApiClient $newValue): void
    {
        $this->client = $newValue;
    }

    /**
     * update token state
     * @param ApiClientToken $newValue
     * @return void
     */
    private function setToken(ApiClientToken $newValue): void
    {
        $this->token = $newValue;
    }

    /**
     * update tokensQuery state
     * @param HasMany $newValue
     * @return void
     */
    private function setTokensQuery(HasMany $newValue)
    {
        $this->tokensQuery = $newValue;
    }

    /**
     * get client token from header
     * @return string
     */
    private function clientToken(): string
    {
        return $this->request->header('Client-Token');
    }

    /**
     * get client IP
     * @return bool
     */
    private function clientIp(): bool
    {
        return $this->request->ip();
    }

    /**
     * get client HTTP host
     * @return string
     */
    private function clientHost(): string
    {
        return $this->request->httpHost();
    }

    /**
     * client request header has token
     * @return bool
     */
    private function clientTokenExistsInHeader(): bool
    {
        return $this->request->hasHeader('Client-Token');
    }

    /**
     * check if token has expired
     * @return bool
     */
    private function tokenHasNotExpired(): bool
    {
        return ( ! $this->token->is_expired);
    }

    /**
     * check if token has been revoked
     * @return bool
     */
    private function tokenHasNotBeenRevoked(): bool
    {
        return ( ! $this->token->is_revoked);
    }

    /**
     * run checks to validate client and token, then do authorization
     * @return mixed
     * @throws Exception
     */
    private function processClientAuthorizationRequest(): mixed
    {
        try {
            $this->ensureRequestIsEncrypted()
                ->verifyClientTokenHeader()
                ->attemptToIdentifyClient()
                ->checkIfClientHasAnyToken()
                ->validateClientProvidedToken()
                ->ensureTokenHasNotExpired()
                ->ensureTokenHasNotBeenRevoked();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());

            if (
                $exception instanceof ApiClientHasNoTokensException
                || $exception instanceof ApiClientTokenExpiredException
            ) {
                return $this->success('', 200, [
                    'token' => $this->getNewlyIssuedToken()
                ]);
            }
            return $this->error(
                $exception->getMessage(),
                $exception->getCode()
            );
        }
        return false;
    }

    /**
     * verify that the client request was sent over HTTPS
     * @throws Exception
     */
    private function ensureRequestIsEncrypted(): ApiRequestAuthorization
    {
        if ($this->request->isSecure()) {
            return $this;
        }
        throw new Exception('Request is not secure.', 403);
    }

    /**
     * verify that the client request header has a client token
     * @throws Exception
     */
    private function verifyClientTokenHeader(): ApiRequestAuthorization
    {
        if ($this->clientTokenExistsInHeader()) {
            return $this;
        }
        throw new Exception('Request has no Client Token.', 403);
    }

    /**
     * verify the identity of the client
     * @throws Exception
     */
    private function attemptToIdentifyClient(): ApiRequestAuthorization
    {
        $clientQuery = ApiClient::query()
            ->where('host', $this->clientHost())
            ->orWhere('ip', $this->clientIp());

        if ($clientQuery->exists()) {
            $this->setClient($clientQuery->get()->first());
            return $this;
        } else throw new Exception('Client does not exist!', 401);
    }

    /**
     * check if client has any tokens in the system
     * @throws Exception
     */
    private function checkIfClientHasAnyToken(): ApiRequestAuthorization
    {
        $tokensQuery = $this->client->apiClientTokens();

        if ($tokensQuery->exists()) {
            $this->setTokensQuery($tokensQuery);
            return $this;
        } else throw new ApiClientHasNoTokensException('Client has not been issued any tokens.', 401);
    }

    /**
     * check that provided token is a token recognized by the system
     * @throws Exception
     */
    private function validateClientProvidedToken(): ApiRequestAuthorization
    {
        $tokensQuery = $this->tokensQuery
            ->where('token', $this->clientToken());

        if ($tokensQuery->exists()) {
            $this->setToken($tokensQuery->get()->first());
            return $this;
        } else throw new ApiClientHasInvalidTokenException('Client has provided Invalid Token.', 401);
    }

    /**
     * check expiration status of token
     * @throws Exception
     */
    private function ensureTokenHasNotExpired(): ApiRequestAuthorization
    {
        if ($this->tokenHasNotExpired()) {
            return $this;
        }

        throw new ApiClientTokenExpiredException(
            'Client Token has expired.',
            401
        );
    }

    /**
     * check if token is revoked or not
     * @throws Exception
     */
    private function ensureTokenHasNotBeenRevoked(): ApiRequestAuthorization
    {
        if ($this->tokenHasNotBeenRevoked()) {
            return $this;
        }

        throw new ApiClientTokenRevokedException(
            'Client Token has been revoked.',
            401
        );
    }

    /**
     * get a new token for client
     * @return string
     */
    private function getNewlyIssuedToken(): string
    {
        return $this->client->issueNewToken();
    }
}
