<?php

namespace TomShaw\GoogleApi\Resources;

use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\FileList;
use TomShaw\GoogleApi\GoogleClient;

final class GoogleDrive
{
    protected Drive $service;

    public function __construct(protected GoogleClient $client)
    {
        $this->service = new Drive($client());
    }

    public function listFiles(array $optParams = []): FileList
    {
        return $this->service->files->listFiles($optParams);
    }

    public function getFile(string $fileId, array $optParams = []): DriveFile
    {
        return $this->service->files->get($fileId, $optParams);
    }

    public function createFile(string $name, string $mimeType, string $content): DriveFile
    {
        $file = new DriveFile;
        $file->setName($name);
        $file->setMimeType($mimeType);

        return $this->service->files->create($file, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
        ]);
    }
}
