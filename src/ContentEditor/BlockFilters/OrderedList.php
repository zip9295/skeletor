<?php

namespace Skeletor\ContentEditor\BlockFilters;

use Skeletor\ContentEditor\Contracts\BlockFilterInterface;

class OrderedList implements BlockFilterInterface
{

    public function filter(array $data): array
    {
        return $data;
    }
}
