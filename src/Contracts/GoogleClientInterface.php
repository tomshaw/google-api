<?php

namespace TomShaw\GoogleApi\Contracts;

use TomShaw\GoogleApi\Storage\StorageAdapterInterface;

interface GoogleClientInterface
{
    public function setStorage(StorageAdapterInterface $tokenStorage): self;

    public function getAccessToken(): ?array;

    public function setAccessToken(array $accessToken): self;

    public function createAuthUrl(): void;

    public function fetchAccessTokenWithRefreshToken($refreshToken): array|bool;

    public function fetchAccessTokenWithAuthCode($authCode): array|bool;

    public function validate(string $accessToken, string $refreshToken, int $expiresIn, string $scope, string $tokenType, int $created): array;
}
