<?php

namespace Skeletor\ContentEditor\Contracts;

interface BlockParserInterface
{
    public function parse(array $data, array $customDataKeys = []): array;
}