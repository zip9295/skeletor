<?php

namespace Skeletor\Attribute\Model;

use Skeletor\Core\Behaviors\Arrayable;
use Skeletor\Core\Model\Model;

class Attribute extends Model
{
    use Arrayable;

    public function __construct(
        private ?int $id,
        private string $name,
        ?\DateTime $createdAt,
        ?\DateTime $updatedAt)
    {
        parent::__construct($createdAt, $updatedAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}