<?php

namespace Skeletor\Page\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Page\Entity\Page;
use Skeletor\Page\Factory\PageFactory;

class PageRepository extends TableViewRepository
{
    const FACTORY = PageFactory::class;
    const ENTITY = Page::class;

    public function __construct(
        EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getSearchableColumns(): array
    {
        return ['title', 'slug'];
    }
}