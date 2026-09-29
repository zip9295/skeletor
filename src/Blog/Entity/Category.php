<?php

namespace Skeletor\Blog\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Seo;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'category')]
class Category
{
    use Seo;

    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    public string $title;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true, nullable: false)]
    public string $slug;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', unique: false)]
    public ?Category $parent;

    #[ORM\Column(type: Types::INTEGER)]
    public int $level;

    public array $children;
}