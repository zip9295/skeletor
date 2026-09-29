<?php

namespace Skeletor\ContentEditor;

use Skeletor\Core\Service\Contracts\CrudServiceInterface;
use Skeletor\ContentEditor\Contracts\BlockFilterFactoryInterface;
use Skeletor\ContentEditor\Contracts\BlockFilterInterface;
use Skeletor\ContentEditor\Exceptions\BlockFilterNotFoundException;

class BlockFilterFactory implements BlockFilterFactoryInterface
{
    protected array $blockFilters = [];

    public function __construct(protected CrudServiceInterface $imageService)
    {

    }


    public function createFilter(string $blockName): BlockFilterInterface
    {
        if(!isset($this->blockFilters[$blockName])) {
            throw new BlockFilterNotFoundException("Block filter for '{$blockName}' not found.");
        }
        return $this->blockFilters[$blockName];
    }

    public function registerBlockFilter(string $blockName, BlockFilterInterface $blockFilter): void
    {
        $this->blockFilters[$blockName] = $blockFilter;
    }
}