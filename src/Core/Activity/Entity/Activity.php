<?php

namespace Skeletor\Core\Activity\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\User\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'activity')]
#[ORM\Index(name: 'idx_entity', columns: ['entityType', 'entityId'])]
#[ORM\Index(name: 'idx_user', columns: ['userId'])]
#[ORM\Index(name: 'idx_action', columns: ['action'])]
class Activity
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 16)]
    public string $action; // 'create', 'update', 'delete'

    #[ORM\Column(type: Types::STRING, length: 64)]
    public string $entityType; // e.g. 'post', 'user', 'category'

    #[ORM\Column(type: Types::INTEGER)]
    public int $entityId;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'userId', referencedColumnName: 'id', nullable: true)]
    public ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $oldData = null; // JSON snapshot before change

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $newData = null; // JSON snapshot after change

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $diff = null; // JSON of changed fields only

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    public ?string $ipAddress = null;
}
