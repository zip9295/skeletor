<?php

namespace Skeletor\ContentEditor\Contracts;

interface BlockViewFilterInterface
{
    public function filter(array $data): array;
}