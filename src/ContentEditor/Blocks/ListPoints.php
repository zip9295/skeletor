<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;

class ListPoints implements BlockParserInterface
{
    const NAME = 'listpoints';

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];
        $points = [];
        if (isset($blockData['points'])) {
            foreach ($blockData['points'] as $point) {
                $points[] = [
                    'title' => $point['title'],
                    'text' => $point['text']
                ];
            }
        }
        $parsedData = [
            'type' => static::NAME,
            'title' => $blockData['title'] ?? '',
            'description' => $blockData['description'] ?? '',
            'columnOneTitle' => $blockData['columnOneTitle'] ?? '',
            'columnTwoTitle' => $blockData['columnTwoTitle'] ?? '',
            'points' => $points
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
            'containerPaddingRight'
        ];
    }
}