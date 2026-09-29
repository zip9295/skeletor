<?php
namespace Skeletor\Lead\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: '`lead`')]
class Lead
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    public ?string $firstName;
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    public ?string $lastName;
    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    public string $email;
    #[ORM\Column(type: Types::STRING, length: 64, unique: false, nullable: true)]
    public ?string $phoneNumber;
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $status;
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    public ?string $source;

}