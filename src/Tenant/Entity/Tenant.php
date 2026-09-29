<?php
namespace Skeletor\Tenant\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Tenant\Model\Tenant as DtoModel;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'tenant')]
class Tenant
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $name;
    #[ORM\Column(type: Types::STRING, length: 512)]
    private string $description;
    #[ORM\Column(type: Types::JSON)]
    private string $settings;
    #[ORM\Column(type: 'string')]
    private string $pib;
    #[ORM\Column(type: 'string')]
    private string $mb;
    #[ORM\Column(type: 'string')]
    private string $email;
    #[ORM\Column(type: 'string')]
    private string $website;
    #[ORM\Column(type: 'string')]
    private string $logo;
    #[ORM\Column(type: 'string')]
    private string $bank;
    #[ORM\Column(type: 'string')]
    private string $accountNumber;
    #[ORM\Column(type: 'integer')]
    private int $isActive;

    public function __construct()
    {
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function populateFromDto(DtoModel $dto)
    {
        $this->id = $dto->getId();
        $this->name = $dto->getName();
        $this->email = $dto->getEmail();
        $this->website = $dto->getWebsite();
        $this->logo = $dto->getLogo();
        $this->isActive = $dto->getIsActive();
        $this->description = $dto->getDescription();

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }
}