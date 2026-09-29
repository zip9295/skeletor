<?php

namespace Skeletor\File\Service;

use GuzzleHttp\Psr7\UploadedFile;

class Processor
{
    public function processUploadedFile(UploadedFile $uploadedFile, $fileName = ''): array
    {
        // @TODO use name field to save input name, filename should be hashed
        if(!is_dir(FILES_PATH)) {
            mkdir(FILES_PATH);
        }
        $uploadedFile->moveTo(FILES_PATH . '/' .  $uploadedFile->getClientFilename());
        return [
            'filename' => $uploadedFile->getClientFilename(),
            'mimeType' => $uploadedFile->getClientMediaType(),
        ];
    }
}