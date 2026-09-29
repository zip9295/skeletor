<?php

namespace Skeletor\ContentEditor;

use Skeletor\ContentEditor\Contracts\BlockParserFactoryInterface;
use Skeletor\ContentEditor\Contracts\ContentEditorParserInterface;

class Parser implements ContentEditorParserInterface
{

    protected array $customBlockData;

    const ALL_BLOCKS = 'allBlocks';

    public function __construct(protected BlockParserFactoryInterface $parserFactory)
    {

    }

    public function parse(array $data): array
    {
        $parsedBlockData = [];
        foreach($data as $blockData) {
            if(!isset($blockData['blockName'])) {
                continue;
            }
            $blockParser = $this->parserFactory->createParser($blockData['blockName']);
            $parsedBlockData[] = $blockParser->parse($blockData, $this->getCustomKeys($blockData['blockName']));
        }
        return $parsedBlockData;
    }

    protected function getCustomKeys(string $blockName): array
    {
        $keysForAllBlocks = $this->customBlockData[static::ALL_BLOCKS] ?? [];
        $keysForBlock = $this->customBlockData[$blockName] ?? [];
        return array_merge($keysForAllBlocks, $keysForBlock);
    }

    public function registerCustomData(string $key, ?string $blockName = null): void
    {
        if(!$blockName) {
            $blockName = static::ALL_BLOCKS;
        }

        $this->customBlockData[$blockName][] = $key;
    }
}