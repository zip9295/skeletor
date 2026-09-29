<?php
namespace Skeletor\Core\Behaviors\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait Timestampable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: "NONE")]
//    #[ORM\CustomIdGenerator(class:UuidGenerator::class)]
    public string|null $id;

    #[ORM\Column(type: 'datetime', insertable: false, updatable: false, options: ['default' => "CURRENT_TIMESTAMP"])]
    public \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', insertable: false, updatable: false, columnDefinition: "DATETIME DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP")]
    public \DateTime $updatedAt;
}
