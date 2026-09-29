<?php
namespace Skeletor\Category\Model;

use Skeletor\Core\Model\Model;

class TenantCategoryMapper extends Model
{
    private $id;
    /**
     * @var Category
     */
    private $localCategory;
    /**
     * @var Category
     */
    private $remoteCategory;
    private $tenant;

    /**
     * @param $tenantCategoryMappingId
     * @param $localCategory
     * @param $remoteCategory
     * @param $tenant
     * @param $createdAt
     * @param $updatedAt
     */
    public function __construct(
        $id, $localCategory = null, $remoteCategory = null, $tenant = null, $createdAt = null, $updatedAt = null
    ) {
        parent::__construct($createdAt, $updatedAt);
        $this->id = $id;
        $this->localCategory = $localCategory;
        $this->remoteCategory = $remoteCategory;
        $this->tenant = $tenant;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Category
     */
    public function getLocalCategory(): Category
    {
        return $this->localCategory;
    }

    /**
     * @return ?Category
     */
    public function getRemoteCategory(): ?Category
    {
        return $this->remoteCategory;
    }

    /**
     * @return mixed
     */
    public function getTenant()
    {
        return $this->tenant;
    }
}