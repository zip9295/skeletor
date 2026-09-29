<?php
declare(strict_types = 1);
namespace Skeletor\Core\Model;

use Skeletor\Core\Behaviors\Arrayable;
use Skeletor\Core\Behaviors\Timestampable;

abstract class Model
{
    use Arrayable, Timestampable;

    private $createdAt;

    private $updatedAt;

    /**
     * Model constructor.
     * @param $createdAt
     * @param $updatedAt
     */
    public function __construct($createdAt, $updatedAt)
    {
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }


    /**
     * @return mixed
     */
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

    abstract public function getId();
}
