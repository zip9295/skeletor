<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;
use Skeletor\Core\Service\Contracts\CrudServiceInterface;
use Skeletor\Image\Service\Image as ImageService;

class Gallery implements BlockParserInterface
{
    const NAME = 'gallery';
    public function __construct(protected CrudServiceInterface $imageService)
    {

    }

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $galleryData = [];
        if(isset($data[static::NAME])) {
            foreach ($data[static::NAME] as $imageId) {
                $image = $this->imageService->getById($imageId);
                $galleryData[] = [
                    'imageId' => $image->id,
                    'filename' => $image->filename,
                    'model' => serialize($image)
                ];
            }
        }
        $parsedData = [
            'type' => static::NAME,
            'data' => $galleryData
        ];

        foreach($customDataKeys as $key) {
            if(isset($data[$key])) {
                $parsedData[$key] = $data[$key];
            }
        }

        return $parsedData;
    }

    protected function getDefaultDataKeys(): array
    {
        return [
            'blockHTMLId',
            'blockHTMLClassName',
            'blockViewMode',
            'containerMarginTop',
            'containerMarginBottom',
            'containerMarginLeft',
            'containerMarginRight',
            'containerPaddingTop',
            'containerPaddingBottom',
            'containerPaddingLeft',
            'containerPaddingRight',
            'galleryType'
        ];
    }
}