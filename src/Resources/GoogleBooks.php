<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Resources;

use Google\Service\Books;
use Google\Service\Books\Volume;
use Google\Service\Books\Volumes;
use TomShaw\GoogleApi\GoogleClient;

final class GoogleBooks
{
    public private(set) Books $service;

    public function __construct(protected GoogleClient $client)
    {
        $this->service = new Books($client());
    }

    /**
     * @param  array<string, mixed>  $optParams
     */
    public function get(string $volumeId, array $optParams = []): Volume
    {
        return $this->service->volumes->get($volumeId, $optParams);
    }

    /**
     * @param  array<string, mixed>  $optParams
     */
    public function listVolumes(string $query, array $optParams = []): Volumes
    {
        return $this->service->volumes->listVolumes($query, $optParams);
    }
}
