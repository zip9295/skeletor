<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;

class Pricing implements BlockParserInterface
{

    const NAME = 'pricing';

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];
        $parsedData = [
            'type' => static::NAME,
            'isBilledAnnually' => $blockData['isBilledAnnually'] ?? 0,
            'cards' => [],
        ];
        if(isset($blockData['cards'])) {
            foreach($blockData['cards'] as $card) {
                $parsedData['cards'][] =  [
                    'title' => $card['title'] ?? '',
                    'price' => $card['price'] ?? '',
                    'annualPricePerMonth' => $card['annualPricePerMonth'] ?? '',
                    'isHighlighted' => $card['isHighlighted'] ?? 0,
                    'belowPriceText' => $card['belowPriceText'] ?? '',
                    'buttonLabel' => $card['buttonLabel'] ?? '',
                    'buttonUrl' => $card['buttonUrl'] ?? '',
                    'featuresTitle' => $card['featuresTitle'] ?? '',
                    'description' => $card['description'] ?? '',
                    'featuresDescription' => $card['featuresDescription'] ?? '',
                    'features' => $this->parseFeatures($card['features'] ?? []),
                ];
            }
        }


        foreach($customDataKeys as $key) {
            if(isset($data[$key])) {
                $parsedData[$key] = $data[$key];
            }
        }

        return $parsedData;
    }

    protected function parseFeatures(array $features): array
    {
        $featuresData = [];
        foreach($features as $feature) {
            $featuresData[] = $this->parseFeature($feature);
        }
        return $featuresData;
    }

    protected function parseFeature(array $feature): array
    {
        return [
            'featureTitle' => $feature['featureTitle'],
            'featureDescription' => $feature['featureDescription'],
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