<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;

class Heading implements BlockParserInterface
{

    const NAME = 'heading';

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $parsedData = [
            'type' => static::NAME,
            'headingType' => $data[static::NAME]['type'],
            'value' => $data[static::NAME]['value'],
            'alignment' => $data[static::NAME]['alignment'],
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