<?php

namespace Skeletor\Image\Model;

use Skeletor\Core\Model\Model;

class ImageRelated extends Model {

    const USE_TYPE_FEATURED_LANDSCAPE = 1;
    const USE_TYPE_FEATURED_PORTRAIT = 2;


    public function __construct(
        private int $id,
        private int $entityType,
        private int $entityId,
        private int $useType,
        private ?Image $image,
        private ?\DateTime $createdAt,
        private ?\DateTime $updatedAt
    ) {
        parent::__construct($this->createdAt, $this->updatedAt);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEntityType(): int
    {
        return $this->entityType;
    }


    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getUseType(): int
    {
        return $this->useType;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }


}