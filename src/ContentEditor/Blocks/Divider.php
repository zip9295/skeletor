<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;

class Divider implements BlockParserInterface
{
    const NAME = 'divider';

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];
        $parsedData = [
            'type' => static::NAME,
            'width' => $blockData['width'] ?? 50,
            'height' => $blockData['height'] ?? 5,
            'marginTop' => $blockData['marginTop'] ?? 0,
            'marginBottom' => $blockData['marginBottom'] ?? 0,
            'color' => $blockData['color'] ?? '#000000',
            'alignment' => $blockData['alignment'] ?? '1',
            'dividerType' => $blockData['dividerType'] ?? '0',

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