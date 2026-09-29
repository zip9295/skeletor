<?php

namespace Skeletor\Author\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Seo;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Image\Entity\Image;

#[ORM\Entity]
#[ORM\Table(name: 'author')]
class Author
{
    use Timestampable;
    use Seo;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    public string $firstName;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false)]
    public string $lastName;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    public ?string $displayName = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $shortDescription = null;

    #[ORM\ManyToOne(targetEntity: Image::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'avatarId', referencedColumnName: 'id', unique: false, nullable: true)]
    public ?Image $avatar = null;

    /**
     * Display name falls back to the full name, which is what the old Model did implicitly at
     * every call site.
     */
    public function getDisplayName(): string
    {
        if ($this->displayName !== null && $this->displayName !== '') {
            return $this->displayName;
        }

        return trim($this->firstName . ' ' . $this->lastName);
    }
}
