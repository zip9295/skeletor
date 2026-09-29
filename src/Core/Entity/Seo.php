<?php

namespace Skeletor\Core\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Image\Entity\Image;

trait Seo
{
    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    public string $seoTitle;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    public string $seoDescription;

    #[ORM\ManyToOne(targetEntity: Image::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'seoImageId', referencedColumnName: 'id', unique: false, nullable: true)]
    public ?Image $seoImage;
}