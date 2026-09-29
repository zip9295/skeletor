<?php

namespace Skeletor\ContentEditor\BlockFilters;

use Skeletor\ContentEditor\Contracts\BlockFilterInterface;

class Html implements BlockFilterInterface
{

    public function filter(array $data): array
    {
        return $data;
    }
}
