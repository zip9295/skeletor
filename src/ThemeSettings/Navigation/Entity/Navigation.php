<?php

namespace Skeletor\ThemeSettings\Navigation\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Behaviors\Entity\Timestampable;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'navigation')]

class Navigation
{
    use \Skeletor\Core\Entity\Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true, nullable: false)]
    public string $label;

    #[ORM\OneToMany(targetEntity: NavigationItem::class, mappedBy: 'navigation', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    public ?Collection $items;

    public function getItemsFormatted(): array
    {
        $grouped = [];

        // Group items by their parent id
        foreach ($this->items as $item) {
            $parentId = $item->parent ? $item->parent->id : null;
            if (!isset($grouped[$parentId])) {
                $grouped[$parentId] = [];
            }
            $grouped[$parentId][] = $item;
        }

        // Sort all groups by position
        foreach ($grouped as &$group) {
            usort($group, function($a, $b) {
                return $a->position - $b->position;
            });
        }

        // Recursive function to build the hierarchy
        $buildTree = function($parentId) use (&$buildTree, &$grouped) {
            $tree = [];
            if (isset($grouped[$parentId])) {
                foreach ($grouped[$parentId] as $item) {
                    $tree[] = [
                        'id' => $item->id,
                        'label' => $item->label,
                        'url' => $item->url,
                        'icon' => $item->icon,
                        'position' => $item->position,
                        'openInNewTab' => $item->openInNewTab,
                        'children' => $buildTree($item->id),
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