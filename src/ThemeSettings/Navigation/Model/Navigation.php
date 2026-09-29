<?php

namespace Skeletor\ThemeSettings\Navigation\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Skeletor\Core\Model\Model;

class Navigation extends Model
{
    public function __construct(
        protected string $id,
        protected string $label,
        protected ?ArrayCollection $items = null,
        protected ?\DateTime $createdAt = null,
        protected ?\DateTime $updatedAt = null
    )
    {
        parent::__construct($createdAt, $updatedAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getItems(): ?ArrayCollection
    {
        return $this->items;

    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function getItemsFormatted(): array
    {
        $grouped = [];

        // Group items by their parent id
        foreach ($this->getItems() as $item) {
            $parentId = $item->getParent() ? $item->getParent()->getId() : null;
            if (!isset($grouped[$parentId])) {
                $grouped[$parentId] = [];
            }
            $grouped[$parentId][] = $item;
        }

        // Sort all groups by position
        foreach ($grouped as &$group) {
            usort($group, function($a, $b) {
                return $a->getPosition() - $b->getPosition();
            });
        }

        // Recursive function to build the hierarchy
        $buildTree = function($parentId) use (&$buildTree, &$grouped) {
            $tree = [];
            if (isset($grouped[$parentId])) {
                foreach ($grouped[$parentId] as $item) {
                    $tree[] = [
                        'id' => $item->getId(),
                        'label' => $item->getLabel(),
                        'url' => $item->getUrl(),
                        'icon' => $item->getIcon(),
                        'position' => $item->getPosition(),
                        'openInNewTab' => $item->getOpenInNewTab(),
                        'children' => $buildTree($item->getId()),
                    ];
                }
            }
            return $tree;
        };

        // Build the hierarchy starting from the root (parentId = null)
        $data =  $buildTree(null);

        usort($data, function($a, $b) {
            return $a['position'] - $b['position'];
        });
        return $data;
    }


}