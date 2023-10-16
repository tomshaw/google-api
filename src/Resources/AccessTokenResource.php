<?php

namespace TomShaw\GoogleApi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // @phpstan-ignore-next-line
            'access_token' => $this->accessToken,
            // @phpstan-ignore-next-line
            'refresh_token' => $this->refreshToken,
            // @phpstan-ignore-next-line
            'expires_in' => $this->expiresIn,
            // @phpstan-ignore-next-line
            'scope' => $this->scope,
            // @phpstan-ignore-next-line
            'token_type' => $this->tokenType,
            // @phpstan-ignore-next-line
            'created' => $this->created,
        ];
    }
}
