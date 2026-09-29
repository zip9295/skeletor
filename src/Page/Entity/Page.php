<?php

namespace Skeletor\Page\Entity;

use Doctrine\DBAL\Types\Types;
use Skeletor\Core\Behaviors\Entity\Timestampable;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Seo;
use Skeletor\Image\Entity\Image;

#[ORM\Entity]
#[ORM\Table(name: 'page')]
class Page
{
    use \Skeletor\Core\Entity\Timestampable;
    use Seo;

    const STATUS_NEW = 0;
    const STATUS_PUBLISHED = 1;
    const STATUS_DRAFT = 2;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    public string $title;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true, nullable: true)]
    public string $slug;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $blockData;

    #[ORM\ManyToOne(targetEntity: Image::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'featuredImageId', referencedColumnName: 'id', unique: false, nullable: true)]
    public ?Image $featuredImage;

    #[ORM\Column(type: Types::INTEGER)]
    public int $status;

    public static function getHrStatuses(): array
    {
        return array(
            self::STATUS_NEW => 'New',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_DRAFT => 'Draft',
        );
    }

    public static function getHrStatus($status): string
    {
        return static::getHrStatuses()[$status];
    }
}