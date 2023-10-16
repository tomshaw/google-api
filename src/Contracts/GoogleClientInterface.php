<?php

namespace TomShaw\GoogleApi\Contracts;

use Google\Client;
use Illuminate\Support\Collection;
use TomShaw\GoogleApi\Models\GoogleToken;

interface GoogleClientInterface
{
    public function getClient(): Client;

    public function createAuthUrl(): void;

    public function getAccessToken(): GoogleToken|Collection|null;

    public function setAccessToken($accessToken): GoogleToken|bool;

    public function fetchAccessTokenWithRefreshToken($refreshToken): array|bool;

    public function fetchAccessTokenWithAuthCode($authCode): array|bool;

    public function validate(string $accessToken, string $refreshToken, int $expiresIn, string $scope, string $tokenType, int $created): array;
}
