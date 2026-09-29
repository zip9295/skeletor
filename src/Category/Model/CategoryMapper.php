<?php
namespace Skeletor\Category\Model;

use Skeletor\Category\Model\Category;
use Skeletor\Core\Model\Model;

class CategoryMapper extends Model
{
    /**
     * @param $categoryMappingId
     * @param $source1
     * @param $source2
     * @param $source3
     * @param $categoryId
     * @param $supplierId
     * @param $status
     * @param $rules
     * @param $limiter
     * @param $margin
     */
    public function __construct(
        private string $id, private string $source1, private string $source2, private string $source3, private int $status, private int $ignored,
        private $supplier = null, private $rules = null, private $limiter = null, private $margin = null, private $count = null,
        private $category = null, $createdAt = null, $updatedAt = null, private $marginGroupId = null
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    public function getIgnored(): int
    {
        return $this->ignored;
    }

    /**
     * @return int
     */
    public function getCount(): int|null
    {
        return $this->count;
    }

    public function getMarginGroupId()
    {
        return $this->marginGroupId;
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
    public function getSource1()
    {
        return $this->source1;
    }

    /**
     * @return mixed
     */
    public function getSource2()
    {
        return $this->source2;
    }

    /**
     * @return mixed
     */
    public function getSource3()
    {
        return $this->source3;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return mixed
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @return mixed
     */
    public function getLimiter()
    {
        return $this->limiter;
    }

    /**
     * @return mixed
     */
    public function getMargin()
    {
        return $this->margin;
    }


}