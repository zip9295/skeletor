<?php

namespace Skeletor\ThemeSettings\SocialLinks\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Behaviors\Entity\Timestampable;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'social_links')]

class SocialLinks
{
    use \Skeletor\Core\Entity\Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true, nullable: false)]
    public string $platform;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    public string $url;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    public int $position;

}