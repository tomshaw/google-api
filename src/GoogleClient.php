<?php

namespace TomShaw\GoogleApi;

use Google\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use TomShaw\GoogleApi\Contracts\GoogleClientInterface;
use TomShaw\GoogleApi\Exceptions\GoogleClientException;
use TomShaw\GoogleApi\Models\GoogleToken;
use TomShaw\GoogleApi\Resources\AccessTokenResource;

class GoogleClient implements GoogleClientInterface
{
    protected Client $client;

    public static $rules = [
        'access_token' => 'required|string',
        'refresh_token' => 'required|string',
        'expires_in' => 'required|numeric',
        'scope' => 'required|string',
        'token_type' => 'required|string',
        'created' => 'required|numeric',
    ];

    public function __construct()
    {
        if (! file_exists(config('google-api.auth_config'))) {
            throw new GoogleClientException('Unable to load client secrets.');
        }

        $this->client = new Client();

        $this->client->setAuthConfig(config('google-api.auth_config'));

        $this->client->addScope(config('google-api.scopes'));

        $this->client->setApplicationName(config('google-api.application_name'));

        $this->client->setPrompt(config('google-api.prompt'));

        $this->client->setApprovalPrompt(config('google-api.approval_prompt'));

        $this->client->setAccessType(config('google-api.access_type'));

        $this->client->setIncludeGrantedScopes(config('google-api.include_grant_scopes'));

        $accessToken = $this->getAccessToken();

        if ($accessToken) {
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
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function createAuthUrl(): void
    {
        $authUrl = $this->client->createAuthUrl();
        header('Location: '.filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
    }

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

    public function setAccessToken($accessToken): GoogleToken|bool
    {
        if (config('google-api.token_storage') === 'session') {
            session(['token' => $accessToken]);

            return true;
        } else {
            return GoogleToken::firstOrCreate(['scope' => $accessToken['scope']], $accessToken);
        }
    }

    public function fetchAccessTokenWithRefreshToken($refreshToken): array|bool
    {
        $response = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

        $resource = new AccessTokenResource($response);

        if (array_key_exists('error', $resource->resource)) {
            throw new GoogleClientException($resource->resource['error']);
        }

        return $this->validate($resource['access_token'], $resource['refresh_token'], $resource['expires_in'], $resource['scope'], $resource['token_type'], $resource['created']);
    }

    public function fetchAccessTokenWithAuthCode($authCode): array|bool
    {
        $response = $this->client->fetchAccessTokenWithAuthCode($authCode);

        $resource = new AccessTokenResource($response);

        if (array_key_exists('error', $resource->resource)) {
            throw new GoogleClientException($resource->resource['error']);
        }

        return $this->validate($resource['access_token'], $resource['refresh_token'], $resource['expires_in'], $resource['scope'], $resource['token_type'], $resource['created']);
    }

    public function validate(string $accessToken, string $refreshToken, int $expiresIn, string $scope, string $tokenType, int $created): array
    {
        $validator = Validator::make(['access_token' => $accessToken, 'refresh_token' => $refreshToken, 'expires_in' => $expiresIn, 'scope' => $scope, 'token_type' => $tokenType, 'created' => $created], self::$rules);

        if ($validator->fails()) {
            throw new GoogleClientException($validator->messages()->first());
        }

        return $validator->validated();
    }
}
