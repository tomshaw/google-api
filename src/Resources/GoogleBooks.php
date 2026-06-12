<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Resources;

use Google\Service\Books;
use Google\Service\Books\Resource\Volumes as VolumesResource;
use Google\Service\Books\Volume;
use Google\Service\Books\Volumes;
use TomShaw\GoogleApi\Exceptions\GoogleApiException;
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
        return $this->volumes()->get($volumeId, $optParams);
    }

    /**
     * @param  array<string, mixed>  $optParams
     */
    public function listVolumes(string $query, array $optParams = []): Volumes
    {
        return $this->volumes()->listVolumes($query, $optParams);
    }

    protected function volumes(): VolumesResource
    {
        $volumes = $this->service->volumes;

        if (! $volumes instanceof VolumesResource) {
            throw new GoogleApiException('The Books volumes resource is unavailable.');
        }

        return $volumes;
    }
}
