<?php
namespace Skeletor\Address\Model;

use Skeletor\Core\Model\Model;

class City extends Model
{
    public function __construct(
        private string $id, private string $name, private string $zip, private ?Country $country, $createdAt = null, $updatedAt = null
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    public function getZip()
    {
        return $this->zip;
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
    public function getCountry()
    {
        return $this->country;
    }
}