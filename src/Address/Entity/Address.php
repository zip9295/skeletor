<?php
namespace Skeletor\Address\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Address\Model\Address as DtoModel;

#[ORM\Entity()]
#[ORM\Table(name: 'address')]
class Address
{
    use Timestampable;
    
    #[ORM\Column(type: Types::STRING)]
    public string $address;
    #[ORM\Column(type: Types::STRING)]
    public string $streetNumber;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $appNumber;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $floor;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $phone;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $name;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $note;
    #[ORM\ManyToOne(targetEntity: City::class, cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'city', referencedColumnName: 'id')]
    public City $city;

//    #[ORM\OneToOne(cascade: ['persist'])]
//    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'entityId')]

    public function getId()
    {
        return $this->id;
    }

    public function setEntity($entity)
    {
        $this->entityId = $entity->getId();
        $this->entityType = $entity->getEntityType();
    }

    public function setCity(City $city)
    {
        $this->city = $city;
    }
}