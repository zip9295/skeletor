<?php
namespace Skeletor\Address\Model;

use Skeletor\Core\Model\Model;

class Country extends Model
{
    public function __construct(
        private string $id, private string $name, $createdAt = null, $updatedAt = null
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

}