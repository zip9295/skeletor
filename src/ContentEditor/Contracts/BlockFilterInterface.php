<?php

namespace Skeletor\ContentEditor\Contracts;

interface BlockFilterInterface
{
    public function filter(array $data): array;
}