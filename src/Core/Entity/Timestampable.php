<?php
namespace Skeletor\Core\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


trait Timestampable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id;

    #[ORM\Column(type: 'datetime', insertable: false, updatable: false, options: ['default' => "CURRENT_TIMESTAMP"])]
    public \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', insertable: false, updatable: false, columnDefinition: "DATETIME DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP")]
    public \DateTime $updatedAt;

    public function getId() : int|string
    {
        return $this->id;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
