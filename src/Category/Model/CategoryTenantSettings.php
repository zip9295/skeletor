<?php
namespace Skeletor\Category\Model;

use Skeletor\Model\Model;

class CategoryTenantSettings extends Model
{
    public function __construct(
        private int $tenantId,
        private Category $category,
        private string $label,
        private string $slug,
        private ?int $id = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    )
    {
        parent::__construct($this->createdAt, $this->updatedAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}