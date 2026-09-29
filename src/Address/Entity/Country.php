<?php
namespace Skeletor\Address\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Address\Model\Country as DtoModel;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'country')]
class Country
{
    use Timestampable;
    
    #[ORM\Column(type: Types::STRING, unique: true)]
    public string $name;

//    #[ORM\OneToMany(targetEntity: City::class, mappedBy: 'country', cascade: ['persist', 'remove'])]
//    private $cities;

    public function getId()
    {
        return $this->id;
    }
}