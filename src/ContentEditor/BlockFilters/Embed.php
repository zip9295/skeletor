<?php

namespace Skeletor\ContentEditor\BlockFilters;

use Skeletor\ContentEditor\Contracts\BlockFilterInterface;

class Embed implements BlockFilterInterface
{

    public function filter(array $data): array
    {
        return $data;
    }
}
