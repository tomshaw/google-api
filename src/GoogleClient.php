<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi;

use BackedEnum;
use Google\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Validator;
use TomShaw\GoogleApi\Exceptions\GoogleClientException;
use TomShaw\GoogleApi\Exceptions\TokenNotFoundException;
use TomShaw\GoogleApi\Storage\StorageAdapterInterface;
use TomShaw\GoogleApi\Storage\UserScopedStorageAdapter;

class GoogleClient
{
    private const array RULES = [
        'access_token' => 'required|string',
        'refresh_token' => 'required|string',
        'expires_in' => 'required|integer',
        'scope' => 'required|string',
        'token_type' => 'required|string',
        'created' => 'required|integer',
    ];

    public function __construct(
        protected Client $client,
        protected StorageAdapterInterface $storageAdapter,
    ) {}

    /**
     * Build a client configured from the google-api config file.
     */
    public static function make(?StorageAdapterInterface $storageAdapter = null): self
    {
        if (! file_exists(config('google-api.auth_config'))) {
            throw new GoogleClientException('Unable to load client secrets.');
        }

        $client = new Client;

        $client->setAuthConfig(config('google-api.auth_config'));

        $client->addScope(config('google-api.service_scopes'));

        $client->setApplicationName(config('google-api.application_name'));

        $client->setPrompt(self::configString('google-api.prompt'));

        $client->setApprovalPrompt(self::configString('google-api.approval_prompt'));

        $client->setAccessType(self::configString('google-api.access_type'));

        $client->setIncludeGrantedScopes(config('google-api.include_grant_scopes'));

        return new self($client, $storageAdapter ?? app(StorageAdapterInterface::class));
    }

    /**
     * @throws TokenNotFoundException When no token is stored; the exception carries the authorization URL.
     */
    public function __invoke(): Client
    {
        $accessToken = $this->getAccessToken();

        if ($accessToken === null) {
            throw new TokenNotFoundException(authUrl: $this->createAuthUrl());
        }

        try {
            $this->client->setAccessToken($accessToken->toArray());
        } catch (\Exception $e) {
            throw new GoogleClientException($e->getMessage());
        }

        if ($this->client->isAccessTokenExpired()) {
            $accessToken = $this->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

            $this->client->setAccessToken($accessToken);

            $this->setAccessToken($accessToken);
        }

        return $this->client;
    }

    public function setStorage(StorageAdapterInterface $storageAdapter): self
    {
        $this->storageAdapter = $storageAdapter;

        return $this;
    }

    public function getStorage(): StorageAdapterInterface
    {
        return $this->storageAdapter;
    }

    public function getAccessToken(): ?AccessToken
    {
        $stored = $this->getStorage()->get();

        if ($stored instanceof Arrayable) {
            $stored = $stored->toArray();
        }

        if (! is_array($stored) || $stored === []) {
            return null;
        }

        return AccessToken::fromArray($stored);
    }

    /**
     * @param  AccessToken|array<string, mixed>  $accessToken
     */
    public function setAccessToken(AccessToken|array $accessToken): self
    {
        if ($accessToken instanceof AccessToken) {
            $accessToken = $accessToken->toArray();
        }

        $this->storageAdapter->set($accessToken);

        return $this;
    }

    public function deleteAccessToken(): void
    {
        $this->getStorage()->delete();
    }

    public function isEmpty(): bool
    {
        return $this->getAccessToken() === null;
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Return a client whose token storage is scoped to the given user.
     */
    public function forUser(Authenticatable|int|string $user): static
    {
        $storage = $this->getStorage();

        if (! $storage instanceof UserScopedStorageAdapter) {
            throw new GoogleClientException(sprintf('The [%s] storage adapter does not support user scoping.', $storage::class));
        }

        $clone = clone $this;
        $clone->client = clone $this->client;
        $clone->storageAdapter = (clone $storage)->forUser($user);

        return $clone;
    }

    /**
     * Build the OAuth2 authorization URL the user should be redirected to.
     */
    public function createAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAccessTokenWithRefreshToken(?string $refreshToken): array
    {
        return $this->resolveAccessTokenResponse($this->client->fetchAccessTokenWithRefreshToken($refreshToken));
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchAccessTokenWithAuthCode(string $authCode): array
    {
        return $this->resolveAccessTokenResponse($this->client->fetchAccessTokenWithAuthCode($authCode));
    }

    /**
     * @param  array<string, mixed>  $token
     * @return array<string, mixed>
     */
    public function validate(array $token): array
    {
        $validator = Validator::make($token, self::RULES);

        if ($validator->fails()) {
            throw new GoogleClientException($validator->messages()->first());
        }

        return $validator->validated();
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function resolveAccessTokenResponse(array $response): array
    {
        if (array_key_exists('error', $response)) {
            $error = $response['error'];

            throw new GoogleClientException(is_string($error) ? $error : (string) json_encode($error));
        }

        return $this->validate($response);
    }

    private static function configString(string $key): string
    {
        $value = config($key);

        return $value instanceof BackedEnum ? (string) $value->value : $value;
    }
}
