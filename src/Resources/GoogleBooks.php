<?php

namespace TomShaw\GoogleApi\Resources;

use Google\Service\Books;
use Google\Service\Books\Volume;
use Google\Service\Books\Volumes;
use TomShaw\GoogleApi\GoogleClient;

final class GoogleBooks
{
    protected Books $service;

    public function __construct(protected GoogleClient $client)
    {
        $this->service = new Books($client());
    }

    public function get(string $volumeId, array $optParams = []): Volume
    {
        return $this->service->volumes->get($volumeId, $optParams);
    }

    public function listVolumes(string $query, array $optParams = []): Volumes
    {
        return $this->service->volumes->listVolumes($query, $optParams);
    }
}
