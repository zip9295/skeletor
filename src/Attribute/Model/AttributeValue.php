<?php

namespace Skeletor\Attribute\Model;

use Skeletor\Core\Behaviors\Arrayable;
use Skeletor\Core\Model\Model;

class AttributeValue extends Model
{
    use Arrayable;
    public function __construct(
        private int $attributeId,
        private string $attributeValue,
        private ?int $id,
        $createdAt,
        $updatedAt
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    /**
     * @return int
     */
    public function getAttributeId(): int
    {
        return $this->attributeId;
    }

    /**
     * @return string
     */
    public function getAttributeValue(): string
    {
        return $this->attributeValue;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

}