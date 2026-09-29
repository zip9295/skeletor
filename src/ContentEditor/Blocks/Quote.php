<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;

class Quote implements BlockParserInterface
{

    const NAME = 'quote';

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $parsedData =  [
            'type' => static::NAME,
            'signature' => $data[static::NAME]['signature'],
            'text' => $data[static::NAME]['text']
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