<?php

namespace TomShaw\GoogleApi\Storage;

use TomShaw\GoogleApi\Models\GoogleToken;

class DatabaseStorageAdapter implements StorageAdapterInterface
{
    public function set(array $accessToken): self
    {
        GoogleToken::updateOrCreate(['user_id' => auth()->id()], array_merge(['user_id' => auth()->id()], $accessToken));

        return $this;
    }

    public function get(): ?GoogleToken
    {
        return GoogleToken::where('user_id', auth()->id())->first();
    }

    public function delete(): void
    {
        GoogleToken::where('user_id', auth()->id())->delete();
    }
}
