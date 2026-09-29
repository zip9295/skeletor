<?php

namespace Skeletor\Notification\Model;

use Skeletor\Core\Model\Model;

class Notification extends Model
{
    const NOTIFICATION_NOT_DISMISSED = 0;
    const NOTIFICATION_DISMISSED = 1;

    public function __construct(
        private ?int $id,
        private string $content,
        private string $linkTo,
        private int $entityId,
        private int $entityType,
        private int $notificationType,
        private ?\DateTime $createdAt,
        private ?\DateTime $updatedAt
    )
    {
        parent::__construct($this->createdAt, $this->updatedAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getLinkTo(): string
    {
        return $this->linkTo;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @return int
     */
    public function getEntityType(): int
    {
        return $this->entityType;
    }

    /**
     * @return int
     */
    public function getNotificationType(): int
    {
        return $this->notificationType;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

}