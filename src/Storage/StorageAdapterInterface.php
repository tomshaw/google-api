<?php

namespace TomShaw\GoogleApi\Storage;

interface StorageAdapterInterface
{
    public function set(array $accessToken): self;

    public function get(): ?array;

    public function delete(): void;
}
