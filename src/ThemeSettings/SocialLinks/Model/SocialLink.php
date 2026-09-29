<?php

namespace Skeletor\ThemeSettings\SocialLinks\Model;

use Skeletor\Core\Model\Model;

class SocialLink extends Model
{
    public function __construct(
        protected string $id,
        protected string $platform,
        protected string $url,
        protected ?\DateTime $createdAt = null,
        protected ?\DateTime $updatedAt = null
    )
    {
        parent::__construct($createdAt, $updatedAt);
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getId()
    {
        return $this->id;
    }
}