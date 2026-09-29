<?php

namespace Skeletor\ContentEditor\BlockFilters;

use Skeletor\ContentEditor\Contracts\BlockFilterInterface;

class HeadingSix implements BlockFilterInterface
{

    public function filter(array $data): array
    {
        return $data;
    }
}
