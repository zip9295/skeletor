<?php
namespace Skeletor\Address\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Address\Model\City as DtoModel;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity()]
#[ORM\Table(name: 'city')]
class City
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING)]
    public string $name;
    #[ORM\Column(type: Types::STRING)]
    public string $zip;
    #[ORM\ManyToOne(targetEntity: Country::class, cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'country', referencedColumnName: 'id')]
    public Country|string $country;

    public function getId()
    {
        return $this->id;
    }

    public function setCountry(Country $country)
    {
        $this->country = $country;
    }
}