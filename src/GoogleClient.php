<?php

namespace TomShaw\GoogleApi;

use Google\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use TomShaw\GoogleApi\Contracts\GoogleClientInterface;
use TomShaw\GoogleApi\Exceptions\GoogleClientException;
use TomShaw\GoogleApi\Models\GoogleToken;
use TomShaw\GoogleApi\Resources\AccessTokenResource;

/**
 * Class GoogleClient
 */
class GoogleClient implements GoogleClientInterface
{
    /**
     * @var array
     */
    public static $rules = [
        'access_token' => 'required|string',
        'refresh_token' => 'required|string',
        'expires_in' => 'required|numeric',
        'scope' => 'required|string',
        'token_type' => 'required|string',
        'created' => 'required|numeric',
    ];

    /**
     * GoogleClient constructor.
     *
     * @throws GoogleClientException
     */
    public function __construct(
        public Client $client
    ) {
        if (! file_exists(config('google-api.auth_config'))) {
            throw new GoogleClientException('Unable to load client secrets.');
        }

        $client->setAuthConfig(config('google-api.auth_config'));

        $client->addScope(config('google-api.service_scopes'));

        $client->setApplicationName(config('google-api.application_name'));

        $client->setPrompt(config('google-api.prompt'));

        $client->setApprovalPrompt(config('google-api.approval_prompt'));

        $client->setAccessType(config('google-api.access_type'));

        $client->setIncludeGrantedScopes(config('google-api.include_grant_scopes'));
    }

    /**
     * Initializes the client.
     *
     * @return GoogleClient The current instance.
     *
     * @throws GoogleClientException If the access token is invalid or missing.
     */
    public function initialize(): GoogleClient
    {
        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            throw new GoogleClientException('Invalid or missing token.');
        }

        if ($accessToken instanceof Collection) {
            $this->client->setAccessToken($accessToken->toArray());
        } else {
            $this->client->setAccessToken($accessToken->makeHidden(['id'])->toArray());
        }

        if ($this->client->isAccessTokenExpired()) {
            $accessRefreshToken = $this->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

            if (is_array($accessRefreshToken)) {
                $this->client->setAccessToken($accessRefreshToken);
                $this->setAccessToken($accessRefreshToken);
            }
        }

        return $this;
    }

    /**
     * Creates an authentication URL and redirects to it.
     */
    public function createAuthUrl(): void
    {
        $authUrl = $this->client->createAuthUrl();
        header('Location: '.filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

    /**
     * Gets the access token.
     *
     * @return GoogleToken|Collection|null The access token.
     */
    public function getAccessToken(): GoogleToken|Collection|null
    {
        if (config('google-api.token_storage') === 'session') {
            if (session()->has('token')) {
                return collect(session('token'));
            } else {
                return null;
            }
        } else {
            return GoogleToken::first();
        }
    }

    /**
     * Sets the access token.
     *
     * @param  mixed  $accessToken The access token to set.
     * @return GoogleToken|bool The new access token, or true if the token was stored in the session.
     */
    public function setAccessToken($accessToken): GoogleToken|bool
    {
        if (config('google-api.token_storage') === 'session') {
            session(['token' => $accessToken]);

            return true;
        } else {
            return GoogleToken::firstOrCreate(['scope' => $accessToken['scope']], $accessToken);
        }
    }

    /**
     * Fetches an access token with a refresh token.
     *
     * @param  mixed  $refreshToken The refresh token to use.
     * @return array|bool The new access token, or false on failure.
     *
     * @throws GoogleClientException If there is an error in the response from the API.
     */
    public function fetchAccessTokenWithRefreshToken($refreshToken): array|bool
    {
        $response = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

        $resource = new AccessTokenResource($response);

        if (array_key_exists('error', $resource->resource)) {
            throw new GoogleClientException($resource->resource['error']);
        }

        return $this->validate($resource['access_token'], $resource['refresh_token'], $resource['expires_in'], $resource['scope'], $resource['token_type'], $resource['created']);
    }

    /**
     * Fetches an access token with an authorization code.
     *
     * @param  mixed  $authCode The authorization code to use.
     * @return array|bool The new access token, or false on failure.
     */
    public function fetchAccessTokenWithAuthCode($authCode): array|bool
    {
        $response = $this->client->fetchAccessTokenWithAuthCode($authCode);

        $resource = new AccessTokenResource($response);

        if (array_key_exists('error', $resource->resource)) {
            throw new GoogleClientException($resource->resource['error']);
        }

        return $this->validate($resource['access_token'], $resource['refresh_token'], $resource['expires_in'], $resource['scope'], $resource['token_type'], $resource['created']);
    }

    /**
     * Validates the provided access token data.
     *
     * @param  string  $accessToken The access token.
     * @param  string  $refreshToken The refresh token.
     * @param  int  $expiresIn The expiration time of the access token, in seconds.
     * @param  string  $scope The scope of the access token.
     * @param  string  $tokenType The type of the token.
     * @param  int  $created The time the token was created, as a Unix timestamp.
     * @return array The validated data.
     *
     * @throws GoogleClientException If validation fails.
     */
    public function validate(string $accessToken, string $refreshToken, int $expiresIn, string $scope, string $tokenType, int $created): array
    {
        $validator = Validator::make(['access_token' => $accessToken, 'refresh_token' => $refreshToken, 'expires_in' => $expiresIn, 'scope' => $scope, 'token_type' => $tokenType, 'created' => $created], self::$rules);

        if ($validator->fails()) {
            throw new GoogleClientException($validator->messages()->first());
        }

        return $validator->validated();
    }
}
