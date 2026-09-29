<?php

namespace Skeletor\ThemeSettings\Navigation\Model;

use Skeletor\Core\Model\Model;


class NavigationItem extends Model
{

    public function __construct(
        protected string $id,
        protected string $label,
        protected string $url,
        protected int $position,
        protected int $openInNewTab,
        protected ?string $icon = null,
        protected ?NavigationItem $parent = null,
        protected ?\DateTime $createdAt = null,
        protected ?\DateTime $updatedAt = null
    )
    {
        parent::__construct($this->createdAt, $this->updatedAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getOpenInNewTab(): int
    {
        return $this->openInNewTab;
    }

    public function getParent(): ?NavigationItem
    {
        return $this->parent;
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