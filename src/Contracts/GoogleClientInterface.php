<?php

namespace TomShaw\GoogleApi\Contracts;

use Illuminate\Support\Collection;
use TomShaw\GoogleApi\GoogleClient;
use TomShaw\GoogleApi\Models\GoogleToken;

interface GoogleClientInterface
{
    public function setAuthConfig(string $authConfig): GoogleClient;

    public function setApplicationName(string $applicationName): GoogleClient;

    public function addScope(array $scopes): GoogleClient;

    public function setAccessType(string $accessType = 'offline'): GoogleClient;

    public function setPrompt(string $prompt = 'none'): GoogleClient;

    public function setApprovalPrompt(string $approvalPrompt = 'offline'): GoogleClient;

    public function setIncludeGrantedScopes(bool $includeGrantedScopes = true): GoogleClient;

    public function createAuthUrl(): void;

    public function getAccessToken(): GoogleToken|Collection|null;

    public function setAccessToken($accessToken): GoogleToken|bool;

    public function fetchAccessTokenWithRefreshToken($refreshToken): array|bool;

    public function fetchAccessTokenWithAuthCode($authCode): array|bool;

    public function validate(string $accessToken, string $refreshToken, int $expiresIn, string $scope, string $tokenType, int $created): array;
}
