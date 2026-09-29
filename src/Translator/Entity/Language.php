<?php
namespace Skeletor\Translator\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'language')]
class Language
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING)]
    public string $name;

    #[ORM\Column(type: Types::STRING)]
    public string $code;

    public function getId()
    {
        return $this->id;
    }

}