<?php

namespace Skeletor\ContentEditor\Contracts;


interface BlockParserFactoryInterface
{
    public function createParser(string $blockName): BlockParserInterface;

    public function registerBlockParser(string $blockName, BlockParserInterface $blockParser): void;
}