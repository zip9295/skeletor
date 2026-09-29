<?php
namespace Skeletor\Category\Model;

use Skeletor\Tenant\Model\Tenant;
use Skeletor\Core\Model\Model;
use Skeletor\Image\Model\Image;

class Category extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * @param $id
     * @param $title
     * @param $image
     * @param $count
     * @param $description
     * @param $level
     * @param $slug
     * @param $status
     * @param Category|null $parent
     * @param int|null $tenant
     * @param $secondDescription
     * @param $createdAt
     * @param $updatedAt
     */
    public function __construct(
        private string $id, private $title, private $slug,
        private $description, private $level, private $status,
        private ?Category $parent = null, $createdAt = null, $updatedAt = null, private ?Image $image = null,
        private $secondDescription = null, private $count = null,  private ?Tenant $tenant = null,
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    public static function getHRStatus($status)
    {
        return static::getHRStatuses()[$status];
    }

    public static function getHRStatuses()
    {
        return [
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_INACTIVE => 'Inactive',
        ];
    }

    /**
     * @return mixed
     */
    public function getSecondDescription()
    {
        return $this->secondDescription;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return Category|string
     */
    public function getParent(): ?Category
    {
        return $this->parent;
    }

    /**
     * @return int|null
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    public function getBlocks(): array
    {
        if (strlen($this->description)) {
            return json_decode($this->description);
        }

        return [];
    }
}