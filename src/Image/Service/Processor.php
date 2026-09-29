<?php
namespace Skeletor\Image\Service;

use GuzzleHttp\Psr7\UploadedFile;
use Skeletor\Core\Config\Config;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Image\Repository\ImageRepository;

class Processor
{
    protected $imagePath;
    protected $cropSizes;

    public function __construct(
        Config $config, private ImageFetcher $fetcher, private Logger $logger, private ImageRepository $imageRepository
    ) {
        if ($config->offsetGet('cropSizes')) {
            $this->cropSizes = $config->offsetGet('cropSizes')->toArray();
        }
        $this->imagePath = $this->createDirectoryStructure();
    }

    public function regenerateImages()
    {
        /* @var \Skeletor\Image\Entity\Image $entity */
        foreach ($this->imageRepository->fetchAll() as $entity) {
            $image = new \Imagick(IMAGES_PATH . $entity->filename);
            $this->createCrops($image);
        }
    }

    public function getCropSizes()
    {
        return $this->cropSizes;
    }

    private function createDirectoryStructure()
    {
        $dt = new \DateTime();
        if (!is_dir(IMAGES_PATH)) {
            mkdir(IMAGES_PATH);
        }
        $dateFolder = IMAGES_PATH . sprintf('/%s/', $dt->format('Y'));
        if (!is_dir($dateFolder)) {
            mkdir($dateFolder);
        }
        $dateFolder .= sprintf('%s/', $dt->format('m'));
        if (!is_dir($dateFolder)) {
            mkdir($dateFolder);
        }
        $dateFolder .= sprintf('%s/', $dt->format('d'));
        if (!is_dir($dateFolder)) {
            mkdir($dateFolder);
        }

        return $dateFolder;
    }

    public function processRemoteFile($url)
    {
        return $this->processImage($this->fetcher->fetch($url, $this->imagePath));
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param $itemId
     *
     * @return array saved file name
     * @throws \Exception
     */
    public function processUploadedFile(UploadedFile $uploadedFile, $fileName = ''): array
    {
        return $this->processImage($this->createTmpImageFromUploadedFile($uploadedFile, $fileName));
    }

    private function createTmpImageFromUploadedFile(UploadedFile $uploadedFile, $fileName)
    {
        $msg = false;
        switch ($uploadedFile->getError()) {
            case UPLOAD_ERR_INI_SIZE:
                $msg = 'Image is bigger than allowed size.';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $msg = 'Image is bigger than allowed size.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $msg = 'Unable to find tmp directory.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $msg = 'Could not write file, check permissions.';
                break;
        }
        if ($msg) {
            throw new \Exception($msg);
        }

        if ($uploadedFile->getClientMediaType() === 'image/png') {
            $ext = '.png';
        } elseif ($uploadedFile->getClientMediaType() === 'image/jpeg' || $uploadedFile->getClientMediaType() === 'image/jpg') {
            $ext = '.jpg';
        } else {
            $msg = sprintf('Unexpected image format "%s" for uploaded file', $uploadedFile->getClientMediaType());
            throw new \Exception($msg);
        }
        $targetPath = $this->imagePath . md5($uploadedFile->getClientFilename() . time()) . $ext;
        if ($fileName) {
            $targetPath = $this->imagePath . $fileName;
        }
        $uploadedFile->moveTo($targetPath);

        return $targetPath;
    }

    public function processImage($tmpFileName)
    {
        $clone = new \Imagick($tmpFileName);
        $image = clone $clone;
        $targetPath = $this->imagePath;
        $parts = explode('.', basename($tmpFileName));
        $orgPath = $parts[0] . '-org.' . $parts[1];
        $scaledPath = $parts[0] . '-org-scaled.' . $parts[1];

        //create org
        $image->writeImage($targetPath . $orgPath);
        if ($image->getImageMimeType() === 'image/png') {
            $tmp = \imagecreatefrompng($targetPath . $orgPath);
            \imagepalettetotruecolor($tmp);
            \imagealphablending($tmp, true);
            \imagesavealpha($tmp, true);
            \imagewebp($tmp, $targetPath . $parts[0] . '-org.webp');
        } elseif ($image->getImageMimeType() === 'image/jpg' || $image->getImageMimeType() === 'image/jpeg') {
            \imagewebp(\imagecreatefromjpeg($targetPath . $orgPath), $targetPath . $parts[0] . '-org.webp');
        }

        // create scaled
        if ($image->getImageWidth() > 3000) {
            $scaled = clone $image;
            $scaled->scaleImage((int) ceil($image->getImageWidth() * 2/3), (int) ceil($image->getImageHeight() * 2/3));
            $scaled->writeImage($targetPath . $scaledPath);
            if ($image->getImageMimeType() === 'image/png') {
                $tmp = \imagecreatefrompng($targetPath . $scaledPath);
                \imagepalettetotruecolor($tmp);
                \imagealphablending($tmp, true);
                \imagesavealpha($tmp, true);
                \imagewebp($tmp, $targetPath . $parts[0] . '-org-scaled.webp');
            } elseif ($image->getImageMimeType() === 'image/jpg' || $image->getImageMimeType() === 'image/jpeg') {
                \imagewebp(\imagecreatefromjpeg($targetPath . $scaledPath), $targetPath . $parts[0] . '-org-scaled.webp');
            }
        }
        $this->createCrops($clone);

        return [
            'savePath' => substr($targetPath, strpos($targetPath, 'images') + 6) . basename($tmpFileName),
            'image' => $image
        ];
    }

    public function createCrops(\Imagick $image, $crops = null)
    {
        if(!$crops) {
            $crops = $this->getCropSizes();
        }
        $ratio = $image->getImageWidth() / $image->getImageHeight();
        foreach ($crops as $name => $cropSizes) {
            $newWidth = $cropSizes[0];
            $newHeight = $cropSizes[1];
            $resize = $cropSizes[2];
            $namedCrop = sprintf('%sx%s', $newWidth, $newHeight);
            if ($resize) {
                if (strstr($name, 'portrait') !== false) {
                    if ($ratio > 1) {
                        continue;
                    }
                }
                if (strstr($name, 'landscape') !== false) {
                    if ($ratio < 1) {
                        continue;
                    }
                }
                if (strstr($name, 'slider') !== false) {
                    // is this slider image ?
                    $sliderCrops = $this->getCropSizes()[SLIDER_1920x644];
                    if (($image->getImageWidth() > $sliderCrops[0] - 40 && $image->getImageWidth() < $sliderCrops[0] + 40) &&
                        ($image->getImageHeight() > $sliderCrops[1] - 20 && $image->getImageHeight() < $sliderCrops[1] + 20)) {

                    } else {
                        continue;
                    }
                }
                $crop = clone $image;
//                $newWidth = 0;
//                if ($crop->getImageWidth() >= $newWidth) {
//                    $newHeight = 0;
//                }
                $crop->scaleImage((int) $newWidth, (int) $newHeight);
            } else {
                $crop = $this->getBestFitCrop($image->getImageFilename(), ['width' => $newWidth, 'height' => $newHeight]);
                if (!$crop) {
                    continue;
                }
            }

            $parts = explode('.', basename($image->getImageFilename()));
            $basePath = strstr($image->getImageFilename(), basename($image->getImageFilename()), true);
            $cropPath = sprintf('%s%s_%s.%s', $basePath, $parts[0], $namedCrop, $parts[1]);
            $cropWebpPath = sprintf('%s%s_%s.%s', $basePath, $parts[0], $namedCrop, 'webp');
            $crop->writeImage($cropPath);
            $crop->destroy();
            if ($image->getImageMimeType() === 'image/png') {
                $tmp = \imagecreatefrompng($cropPath);
                \imagepalettetotruecolor($tmp);
                \imagealphablending($tmp, true);
                \imagesavealpha($tmp, true);
                \imagewebp($tmp, $cropWebpPath);
            } elseif ($image->getImageMimeType() === 'image/jpg' || $image->getImageMimeType() === 'image/jpeg') {
                \imagewebp(\imagecreatefromjpeg($cropPath), $cropWebpPath);
            }
        }
    }

    private function getBestFitCrop($path, $size)
    {
        $image = new \Imagick($path);
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        $imgRatio = $width / $height;
        $cropRatio = $size['width'] / $size['height'];
        if ($height < $size['height'] || $width < $size['width']) {
            return null;
//            throw new \Exception('image too small');
        }
//        $maxSize = min($width, $height);
        if ($width > $height) { // landscape, cube
            if ($cropRatio >= $imgRatio) {
                $maxWidth = $width;
                $maxHeight = ceil($size['height'] * ($width / $size['width'])); // img width divided by crop width for resize ratio
                $hOffset = floor(($height - $maxHeight) / 2);
                $wOffset = 0;
            } else {
                $maxHeight = $height;
                $maxWidth = ceil($size['width'] * ($height / $size['height']));
                $wOffset = floor(($width - $maxWidth) / 2);
                $hOffset = 0;
            }
        } else { // portrait
            if ($cropRatio > $imgRatio) {
                $maxWidth = $width;
                $maxHeight = ceil($size['height'] * ($width / $size['width']));
                $hOffset = floor(($height - $maxHeight) / 2);
                $wOffset = 0;
            } else {
                $maxHeight = $height;
                $maxWidth = ceil($size['width'] * ($height / $size['height']));
                $wOffset = floor(($width - $maxWidth) / 2);
                $hOffset = 0;
            }
        }

        try {
            $image->setImageCompression(\Imagick::COMPRESSION_LOSSLESSJPEG);
            $image->setImageCompressionQuality(60);
            $image->cropImage($maxWidth, $maxHeight, $wOffset, $hOffset);
            $image->resizeImage($size['width'], $size['height'], \Imagick::FILTER_CATROM, 1);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            $msg = '%s path: %s maxWidth: %s maxHeight %s hOffset %s woffset %s';
            $msg = sprintf($msg, $path, $e->getMessage(), $maxWidth, $maxHeight, $hOffset, $wOffset);
            $this->logger->error($msg);
            throw new \Exception('image failed to optimize:' . $msg);
        }
        return $image;
    }
}