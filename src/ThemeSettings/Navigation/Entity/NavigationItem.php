<?php

namespace Skeletor\ThemeSettings\Navigation\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Behaviors\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'navigationItem')]

class NavigationItem
{
    use \Skeletor\Core\Entity\Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    public string $label;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    public string $url;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $icon;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    public int $position;

    #[ORM\Column(type: Types::SMALLINT, nullable: false, options: ['default' => 0])]
    public int $openInNewTab;

    #[ORM\ManyToOne(targetEntity: NavigationItem::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'id', unique: false, nullable: true, onDelete: 'SET NULL')]
    public ?NavigationItem $parent;

    #[ORM\ManyToOne(targetEntity: Navigation::class, fetch: 'EAGER', inversedBy: 'items')]
    public Navigation $navigation;
}