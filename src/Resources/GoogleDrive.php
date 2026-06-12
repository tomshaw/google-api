<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Resources;

use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\FileList;
use Google\Service\Drive\Resource\Files as FilesResource;
use TomShaw\GoogleApi\Exceptions\GoogleApiException;
use TomShaw\GoogleApi\GoogleClient;

final class GoogleDrive
{
    public private(set) Drive $service;

    public function __construct(protected GoogleClient $client)
    {
        $this->service = new Drive($client());
    }

    /**
     * @param  array<string, mixed>  $optParams
     */
    public function listFiles(array $optParams = []): FileList
    {
        return $this->files()->listFiles($optParams);
    }

    /**
     * @param  array<string, mixed>  $optParams
     */
    public function getFile(string $fileId, array $optParams = []): DriveFile
    {
        return $this->files()->get($fileId, $optParams);
    }

    public function createFile(string $name, string $mimeType, string $content): DriveFile
    {
        $file = new DriveFile;
        $file->setName($name);
        $file->setMimeType($mimeType);

        return $this->files()->create($file, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
        ]);
    }

    protected function files(): FilesResource
    {
        $files = $this->service->files;

        if (! $files instanceof FilesResource) {
            throw new GoogleApiException('The Drive files resource is unavailable.');
        }

        return $files;
    }
}
