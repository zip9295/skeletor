<?php

namespace Skeletor\Blog\Repository;

use Skeletor\Blog\Entity\Tag;
use Skeletor\Blog\Factory\TagFactory;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class TagRepository extends TableViewRepository
{
    const ENTITY = Tag::class;

    const FACTORY = TagFactory::class;

    function getSearchableColumns(): array
    {
        return ['a.title'];
    }
}