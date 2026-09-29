<?php

namespace Skeletor\Tag\Repository;

use Skeletor\Core\TableView\Repository\TableViewRepository;

class Tag extends TableViewRepository implements TagRepoInterface
{
    const ENTITY = \Skeletor\Tag\Entity\Tag::class;
    const FACTORY = \Skeletor\Tag\Model\TagFactory::class;

    function getSearchableColumns(): array
    {
        return ['title'];
    }
}