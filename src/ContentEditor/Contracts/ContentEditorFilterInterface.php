<?php


namespace Skeletor\ContentEditor\Contracts;
interface ContentEditorFilterInterface
{
    public function filter(array $data): array;
}