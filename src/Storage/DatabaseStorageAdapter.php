<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Storage;

use Illuminate\Contracts\Auth\Authenticatable;
use TomShaw\GoogleApi\Models\GoogleToken;

class DatabaseStorageAdapter implements UserScopedStorageAdapter
{
    protected Authenticatable|int|string|null $user = null;

    public function forUser(Authenticatable|int|string|null $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $accessToken
     */
    public function set(array $accessToken): self
    {
        GoogleToken::updateOrCreate(['user_id' => $this->userId()], array_merge(['user_id' => $this->userId()], $accessToken));

        return $this;
    }

    public function get(): ?GoogleToken
    {
        return GoogleToken::where('user_id', $this->userId())->first();
    }

    public function delete(): void
    {
        GoogleToken::where('user_id', $this->userId())->delete();
    }

    protected function userId(): int|string|null
    {
        if ($this->user instanceof Authenticatable) {
            return $this->user->getAuthIdentifier();
        }

        return $this->user ?? auth()->id();
    }
}
