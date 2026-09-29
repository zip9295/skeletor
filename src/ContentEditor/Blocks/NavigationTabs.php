<?php

namespace Skeletor\ContentEditor\Blocks;

use Skeletor\ContentEditor\Contracts\BlockParserInterface;

class NavigationTabs implements BlockParserInterface
{
    const NAME = 'navigationtabs';


    public function parse(array $data, array $customDataKeys = []): array
    {
        $customDataKeys = array_merge($customDataKeys, $this->getDefaultDataKeys());
        $blockData = $data[static::NAME] ?? [];

        $parsedData =  [
            'type' => static::NAME,
            'title' => $blockData['title'] ?? '',
            'description' => $blockData['description'] ?? '',
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
            'targetId' => $tab['targetId'] ?? null,
            'label' => $tab['label'] ?? '',
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