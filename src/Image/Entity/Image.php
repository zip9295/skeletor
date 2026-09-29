<?php
namespace Skeletor\Image\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'image')]
class Image
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING)]
    public string $filename;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $alt;

    #[ORM\Column(type: Types::INTEGER)]
    public int $type;

    #[ORM\Column(type: Types::STRING)]
    public string $mimeType;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $label;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $author;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $orientation;

    public function getId()
    {
        return $this->id;
    }
}