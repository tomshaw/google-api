<?php

namespace TomShaw\GoogleApi;

use Google\Client;
use Illuminate\Support\Facades\Validator;
use TomShaw\GoogleApi\Contracts\GoogleClientInterface;
use TomShaw\GoogleApi\Exceptions\GoogleClientException;
use TomShaw\GoogleApi\Resources\AccessTokenResource;
use TomShaw\GoogleApi\Storage\StorageAdapterInterface;

class GoogleClient implements GoogleClientInterface
{
    protected StorageAdapterInterface $storageAdapter;

    public static $rules = [
        'access_token' => 'required|string',
        'refresh_token' => 'required|string',
        'expires_in' => 'required|numeric',
        'scope' => 'required|string',
        'token_type' => 'required|string',
        'created' => 'required|numeric',
    ];

    public function __construct(
        protected Client $client,
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

        $this->setStorage(app(config('google-api.token_storage_adapter')));
    }

    public function __invoke(): Client
    {
        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            throw new GoogleClientException('Invalid or missing token.');
        }

        try {
            $this->client->setAccessToken($accessToken);
        } catch (\Exception $e) {
            throw new GoogleClientException($e->getMessage());
        }

        if ($this->client->isAccessTokenExpired()) {
            $accessToken = $this->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

            if (is_array($accessToken)) {
                $this->client->setAccessToken($accessToken);

                $this->setAccessToken($accessToken);
            }
        }

        return $this->client;
    }

    public function setStorage(StorageAdapterInterface $storageAdapter): self
    {
        if (! $storageAdapter instanceof StorageAdapterInterface) {
            throw new GoogleClientException('Invalid token storage.');
        }

        $this->storageAdapter = $storageAdapter;

        return $this;
    }

    public function getStorage(): StorageAdapterInterface
    {
        return $this->storageAdapter;
    }

    public function getAccessToken(): ?array
    {
        return $this->storageAdapter->get();
    }

    public function setAccessToken(array $accessToken): self
    {
        $this->storageAdapter->set($accessToken);

        return $this;
    }

    public function createAuthUrl(): void
    {
        $authUrl = $this->client->createAuthUrl();
        header('Location: '.filter_var($authUrl, FILTER_SANITIZE_URL));
        exit;
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
