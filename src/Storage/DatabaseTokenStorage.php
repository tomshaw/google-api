<?php

namespace TomShaw\GoogleApi\Storage;

use TomShaw\GoogleApi\Models\GoogleToken;

class DatabaseTokenStorage implements StorageAdapterInterface
{
    public function set(array $accessToken): self
    {
        GoogleToken::updateOrCreate(['user_id' => auth()->id()], array_merge(['user_id' => auth()->id()], $accessToken));

        return $this;
    }

    public function get(): ?array
    {
        $token = GoogleToken::where('user_id', auth()->id())->first();

        return $token ? $token->toArray() : null;
    }

    public function delete(): void
    {
        GoogleToken::truncate();
    }
}
