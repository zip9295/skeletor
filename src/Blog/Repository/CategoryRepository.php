<?php

namespace Skeletor\Blog\Repository;

use Skeletor\Blog\Entity\Category;
use Skeletor\Blog\Factory\CategoryFactory;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class CategoryRepository extends TableViewRepository
{
    const ENTITY = Category::class;

    const FACTORY = CategoryFactory::class;

    public function getSearchableColumns(): array
    {
        return ['a.title', 'a.slug'];
    }
}