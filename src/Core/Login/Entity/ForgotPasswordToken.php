<?php
namespace Skeletor\Core\Login\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Core\Login\Model\ForgotPasswordToken as DtoModel;

#[ORM\Entity]
#[ORM\Table(name: 'forgotPasswordToken')]
class ForgotPasswordToken
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 128)]
    public string $token;

    #[ORM\Column(type: Types::STRING)]
    public string $entityId;

    /**
     * The registered entity type this token belongs to ("user", "delegate", ...).
     *
     * Was an integer that every caller hardcoded to 1, which meant a user and a delegate
     * with the same id shared a token namespace. Widened to the same string key the entity
     * registry and magic-link tokens already use.
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    public string $entityType;

    public function populateFromDto(DtoModel $dto)
    {
        $this->id = $dto->getId();
        $this->token = $dto->getToken();
        $this->entityId = $dto->getEntityId();
        $this->entityType = $dto->getEntityType();
    }

    public function resetToken()
    {
        $this->token = '';
    }

    public function getId()
    {
        return $this->id;
    }
}