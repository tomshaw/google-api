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
     * @param  array<array-key, mixed>  $token
     */
    public static function fromArray(array $token): self
    {
        return new self(
            accessToken: self::stringValue($token['access_token'] ?? null),
            refreshToken: self::stringValue($token['refresh_token'] ?? null),
            expiresIn: self::intValue($token['expires_in'] ?? null),
            scope: self::stringValue($token['scope'] ?? null),
            tokenType: self::stringValue($token['token_type'] ?? null),
            created: self::intValue($token['created'] ?? null),
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

    private static function stringValue(mixed $value): ?string
    {
        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            default => null,
        };
    }

    private static function intValue(mixed $value): ?int
    {
        return match (true) {
            is_int($value) => $value,
            is_numeric($value) => (int) $value,
            default => null,
        };
    }
}
