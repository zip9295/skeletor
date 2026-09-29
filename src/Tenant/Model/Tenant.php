<?php
namespace Skeletor\Tenant\Model;

use Skeletor\Address\Model\Address;

class Tenant extends \Skeletor\Core\Model\Model
{
    public function __construct(
        private string $name, private string $description, private string $email, private string $website, private ?string $logo,
        private int $isActive, private string $id, $createdAt = null, $updatedAt = null
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    /**
     * @return int
     */
    public function getIsActive(): int
    {
        return $this->isActive;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getWebsite(): string
    {
        return $this->website;
    }

    /**
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

}