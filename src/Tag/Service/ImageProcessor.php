<?php

namespace Skeletor\Tag\Service;

use Skeletor\Image\Service\Processor;
use Skeletor\Core\Config\Config;

class ImageProcessor extends Processor
{
    public function __construct(Config $config)
    {
        parent::__construct($config);
    }

    /**
     * @throws \ImagickException
     */
    public function processImage($tmpFileName): string
    {
        $tmpImage = new \Imagick($tmpFileName);
//        $tmpImage->resizeImage(50, 50, \Imagick::FILTER_CATROM, 1);
        $targetPath = $this->imagePath;
        $baseFileName = basename($tmpFileName);
        $extension = pathinfo($baseFileName, PATHINFO_EXTENSION);
        $targetPath .= $baseFileName . '.' . $extension;
        $tmpImage->writeImage($targetPath);
        unlink($tmpFileName);
        return substr($targetPath, strpos($targetPath, 'images') + 6);
    }

}