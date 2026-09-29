<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;
use Skeletor\Core\Service\Contracts\CrudServiceInterface;

class Tabs implements BlockParserInterface
{

    const NAME = 'tabs';

    public function __construct(protected CrudServiceInterface $imageService)
    {

    }

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];
        $parsedData = [
            'type' => static::NAME,
            'title' => $blockData['title'] ?? '',
            'showExpandButton' => $blockData['showExpandButton'] ?? 0,
            'tabs' => $this->parseTabs($blockData['tabs'] ?? []),
        ];

        foreach($customDataKeys as $key) {
            if(isset($data[$key])) {
                $parsedData[$key] = $data[$key];
            }
        }
        return $parsedData;
    }

    protected function parseTabs(array $tabs): array
    {
        $tabsData = [];
        foreach($tabs as $tab) {
            $tabsData[] = $this->parseTab($tab);
        }
        return $tabsData;
    }

    protected function parseTab(array $tab): array
    {
        return [
            'title' => $tab['title'] ?? '',
            'label' => $tab['label'] ?? '',
            'elements' => $this->parseElements($tab['elements'] ?? []),
        ];
    }

    protected function parseElements(array $elements): array
    {
        $elementsData = [];
        foreach($elements as $element) {
            $elementsData[] = $this->parseElement($element);
        }
        return $elementsData;
    }

    protected function parseElement(array $element): array
    {
        return [
            'elementTitle' => $element['elementTitle'] ?? '',
            'elementTitleUrl' => $element['elementTitleUrl'] ?? '',
            'elementDescription' => $element['elementDescription'] ?? '',
            'elementImage' => $this->getImageDataForElement($element),
        ];
    }

    protected function getImageDataForElement(array $element): array
    {
        if(!$element['imageId']) {
            $image = null;
        } else {
            $image = $this->imageService->getById($element['imageId']);
        }
        return [
            'imageId' => $image?->id,
            'filename' => $image?->filename,
            'alt' => $image?->alt,
            'author' => $image?->author,
            'label' => $image?->label,
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