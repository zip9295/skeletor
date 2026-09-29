<?php
namespace Skeletor\Address\Model;

use Skeletor\Core\Model\Model;

class Address extends Model
{
    /**
     * @param string $id
     * @param string $address
     * @param string $streetNumber
     * @param string|null $floor
     * @param string|null $appNumber
     * @param string|null $name
     * @param string|null $note
     * @param string $phone
     * @param City $city
     * @param Country $country
     * @param $entityId
     * @param $entityType
     * @param $createdAt
     * @param $updatedAt
     */
    public function __construct(
        private string $id, private string $address, private string $streetNumber, private ?City $city,
        private ?string $appNumber = null, private ?string $name = null, private ?string $note = null, private ?string $phone = null,
        private $entityId = null, private ?string $floor = null, private $entityType = null, $createdAt = null, $updatedAt = null
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return City
     */
    public function getCity() : City
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return mixed
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * @return mixed
     */
    public function getAppNumber()
    {
        return $this->appNumber;
    }

    /**
     * @return mixed
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }
}
