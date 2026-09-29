<?php

namespace Skeletor\ContentEditor\BlockFilters;

use Skeletor\ContentEditor\Contracts\BlockFilterInterface;

class Tabs implements BlockFilterInterface
{

    public function filter(array $data): array
    {
        return $data;
    }
}
