<?php
namespace Skeletor\File\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\File\Model\File as DtoModel;

#[ORM\Entity]
#[ORM\Table(name: 'file')]
class File
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING)]
    public string $filename;
    #[ORM\Column(type: Types::STRING)]
    public string $mimeType;

    public function getId()
    {
        return $this->id;
    }
}