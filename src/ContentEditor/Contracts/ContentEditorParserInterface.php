<?php

namespace Skeletor\ContentEditor\Contracts;

interface ContentEditorParserInterface
{
    public function parse(array $data): array;

    public function registerCustomData(string $key, ?string $blockName = null): void;
}