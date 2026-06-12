<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi;

final readonly class AccessToken
{
    public function __construct(
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
        public ?int $expiresIn = null,
        public ?string $scope = null,
        public ?string $tokenType = null,
        public ?int $created = null,
    ) {}

    /**
     * @param  array<string, mixed>  $token
     */
    public static function fromArray(array $token): self
    {
        return new self(
            accessToken: isset($token['access_token']) ? (string) $token['access_token'] : null,
            refreshToken: isset($token['refresh_token']) ? (string) $token['refresh_token'] : null,
            expiresIn: isset($token['expires_in']) ? (int) $token['expires_in'] : null,
            scope: isset($token['scope']) ? (string) $token['scope'] : null,
            tokenType: isset($token['token_type']) ? (string) $token['token_type'] : null,
            created: isset($token['created']) ? (int) $token['created'] : null,
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return array_filter([
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_in' => $this->expiresIn,
            'scope' => $this->scope,
            'token_type' => $this->tokenType,
            'created' => $this->created,
        ], fn (int|string|null $value): bool => $value !== null);
    }

    public function hasRefreshToken(): bool
    {
        return $this->refreshToken !== null && $this->refreshToken !== '';
    }

    public function isExpired(int $leewaySeconds = 30): bool
    {
        if ($this->created === null || $this->expiresIn === null) {
            return true;
        }

        return ($this->created + $this->expiresIn - $leewaySeconds) <= time();
    }
}
