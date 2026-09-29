<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;
use Skeletor\Core\Service\Contracts\CrudServiceInterface;

class Slider implements BlockParserInterface
{

    const NAME = 'slider';

    public function __construct(protected CrudServiceInterface $imageService)
    {

    }

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];

        $parsedData =  [
            'type' => static::NAME,
            'autoPlay' => $blockData['autoPlay'] ?? 0,
            'interval' => $blockData['interval'] ?? '',
            'arrows' => $blockData['arrows'] ?? 0,
            'bullets' => $blockData['bullets'] ?? 0,
            'loop' => $blockData['loop'] ?? 0,
            'slidesPerView' => $blockData['slidesPerView'] ?? '',
            'slides' => $this->parseSlides($blockData['slides'] ?? []),
        ];

        foreach($customDataKeys as $key) {
            if(isset($data[$key])) {
                $parsedData[$key] = $data[$key];
            }
        }

        return $parsedData;
    }

    protected function parseSlides(array $slides): array
    {
        $slidesData = [];
        foreach($slides as $slide) {
            $slidesData[] = $this->parseSlide($slide);
        }
        return $slidesData;
    }

    protected function parseSlide(array $slide): array
    {
        $baseData = $this->getBaseDataForSlide($slide);
        $portraitImageData = $this->getPortraitImageDataForSlide($slide);

        return array_merge($baseData, $portraitImageData);
    }

    protected function getPortraitImageDataForSlide(array $slide): array
    {
        if(!$slide['portraitImageId']) {
            $portraitImage = null;
        } else {
            $portraitImage = $this->imageService->getById($slide['portraitImageId']);
        }
        return [
            'portraitImageId' => $portraitImage?->id,
            'portraitFilename' => $portraitImage?->filename,
            'portraitAlt' => $portraitImage?->alt,
            'portraitAuthor' => $portraitImage?->author,
            'portraitLabel' => $portraitImage?->label,
        ];
    }

    private function getBaseDataForSlide(array $slide): array
    {
        return [
            'title' => $slide['title'],
            'link' => $slide['link'],
            'embed' => $slide['embed'],
        ];
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
            'containerPaddingRight'
        ];
    }
}