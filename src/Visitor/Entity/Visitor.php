<?php
namespace Skeletor\Visitor\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Visitor\Model\Visitor as DtoModel;

#[ORM\Entity]
#[ORM\Table(name: 'visitor')]
class Visitor
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $firstName;
    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $lastName;
    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    private string $email;
    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $password;
    #[ORM\Column(type: Types::SMALLINT, length: 1)]
    private string $role;
    #[ORM\Column(type: Types::INTEGER)]
    private string $isActive;
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    private ?string $ipv4;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastLogin;


    public function populateFromDto(DtoModel $dto)
    {
        if ($dto->getId()) {
            $this->id = $dto->getId();
        }
        $this->firstName = $dto->getFirstName();
        $this->lastLogin = $dto->getLastLogin();
        $this->lastName = $dto->getLastName();
        $this->email = $dto->getEmail();
        if ($dto->getPassword()) {
            $this->password = $dto->getPassword();
        }
        $this->isActive = $dto->getIsActive();
        $this->role = $dto->getRole();

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function updateLoginInfo($ipv4, $lastLogin)
    {
        $this->ipv4 = $ipv4;
        $this->lastLogin = $lastLogin;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }
}