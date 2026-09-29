<?php
namespace Skeletor\Reference\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'reference')]
class Reference
{
    use Timestampable;
    const STATUS_PUBLISHED = 1;
    const STATUS_DRAFT = 0;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    public string $title;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $comment;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    public string $content;

    #[ORM\Column(type: Types::INTEGER)]
    public int $status;

    public static function getHrStatuses(): array
    {
        return [
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_DRAFT => 'Draft'
        ];
    }

    public static function getHrStatus(int $status): string
    {
        return self::getHrStatuses()[$status];
    }
}