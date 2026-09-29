<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;

class Accordion implements BlockParserInterface
{

    const NAME = 'accordion';

    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];
        $panels = [];
        if (isset($blockData['panel'])) {
            foreach ($blockData['panel'] as $panel) {
                $panels[] = [
                    'title' => $panel['title'] ?? null,
                    'text' => $panel['text'] ?? null
                ];
            }
        }
        $parsedData = [
            'type' => static::NAME,
            'title' => $blockData['title'] ?? null,
            'description' => $blockData['description'] ?? null,
            'columns' => $blockData['columns'] ?? 1,
            'panels' => $panels
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