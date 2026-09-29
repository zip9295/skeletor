<?php

namespace Skeletor\Blog\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Seo;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'tag')]
class Tag
{
    use Seo;

    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    public string $title;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true, nullable: false)]
    public string $slug;
}