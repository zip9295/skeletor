<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;
use Skeletor\Core\Service\Contracts\CrudServiceInterface;

class Cards implements BlockParserInterface
{
    const NAME = 'cards';

    public function __construct(protected CrudServiceInterface $imageService)
    {

    }
    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];

        $parsedData =  [
            'type' => static::NAME,
            'title' => $blockData['title'] ?? '',
            'link' => $blockData['link'] ?? '',
            'linkText' => $blockData['linkText'] ?? '',
            'cards' => $this->parseCards($blockData['card'] ?? []),
        ];

        foreach($customDataKeys as $key) {
            if(isset($data[$key])) {
                $parsedData[$key] = $data[$key];
            }
        }

        return $parsedData;
    }

    protected function parseCards(array $cards): array
    {
        $cardsData = [];
        foreach($cards as $card) {
            $cardsData[] = $this->parseCard($card);
        }
        return $cardsData;
    }

    protected function parseCard(array $card): array
    {
        $baseData = $this->getBaseDataForCard($card);
        $imageData = $this->getImageDataForCard($card);

        return array_merge($baseData, $imageData);
    }

    protected function getImageDataForCard(array $card): array
    {
        if(!$card['imageId']) {
            $image = null;
        } else {
            $image = $this->imageService->getById($card['imageId']);
        }
        return [
            'imageId' => $image?->id,
            'filename' => $image?->filename,
            'alt' => $image?->alt,
            'author' => $image?->author,
            'label' => $image?->label,
        ];
    }

    private function getBaseDataForCard(array $card): array
    {
        return [
            'title' => $card['title'],
            'link' => $card['link'],
            'description' => $card['description'],
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