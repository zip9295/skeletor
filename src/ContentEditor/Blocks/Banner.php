<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;
use Skeletor\Core\Service\Contracts\CrudServiceInterface;

class Banner implements BlockParserInterface
{

    const NAME = 'banner';

    public function __construct(protected CrudServiceInterface $imageService)
    {

    }

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];

        $parsedData =  [
            'type' => static::NAME,
            'content' => $blockData['content'] ?? '',
            'landscapeImage' => $this->getLandscapeImageData($blockData),
            'portraitImage' => $this->getPortraitImageData($blockData),
            'linkTo' => $blockData['linkTo'] ?? '',
            'openInNewTab' => $blockData['openInNewTab'] ?? 0,
            'embed' => $blockData['embed'] ?? '',
            'buttons' => $this->parseButtons($blockData['buttons'] ?? []),
        ];

        foreach($customDataKeys as $key) {
            if(isset($data[$key])) {
                $parsedData[$key] = $data[$key];
            }
        }

        return $parsedData;
    }

    protected function parseButtons(array $buttons): array
    {
        $buttonsData = [];
        foreach($buttons as $button) {
            $buttonsData[] = $this->parseButton($button);
        }
        return $buttonsData;
    }

    protected function parseButton(array $button): array
    {
        return [
            'title' => $button['title'],
            'url' => $button['url'],
            'openInNewTab' => $button['openInNewTab'],
            'color' => $button['color'] ?? '',
            'textColor' => $button['textColor'] ?? '',
        ];
    }

    protected function getLandscapeImageData(array $data): array
    {
        if(!$data['landscapeImageId']) {
            $landscapeImage = null;
        } else {
            $landscapeImage = $this->imageService->getById($data['landscapeImageId']);
        }
        return [
            'landscapeImageId' => $landscapeImage?->id,
            'landscapeFilename' => $landscapeImage?->filename,
            'landscapeAlt' => $landscapeImage?->alt,
            'landscapeAuthor' => $landscapeImage?->author,
            'landscapeLabel' => $landscapeImage?->label,
        ];
    }

    protected function getPortraitImageData(array $data): array
    {
        if(!$data['portraitImageId']) {
            $portraitImage = null;
        } else {
            $portraitImage = $this->imageService->getById($data['portraitImageId']);
        }
        return [
            'portraitImageId' => $portraitImage?->id,
            'portraitFilename' => $portraitImage?->filename,
            'portraitAlt' => $portraitImage?->alt,
            'portraitAuthor' => $portraitImage?->author,
            'portraitLabel' => $portraitImage?->label,
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