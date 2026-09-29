<?php

namespace Skeletor\Review\Model;

use Skeletor\Core\Behaviors\Arrayable;
use Skeletor\Core\Model\Model;
use Skeletor\Visitor\Model\Visitor;

class Review extends Model
{
    const STATUS_NEW = 0;
    const STATUS_PUBLISHED = 1;
    const STATUS_UNPUBLISHED = 2;

    const UPDATED = 'review.updated';
    const CREATED = 'review.created';
    const DELETED = 'review.deleted';

    public function __construct(
        private int $id,
        private string $body,
        private int $entityId,
        private int $entityType,
        private Model $entity,
        private ?Visitor $visitor,
        private string $email,
        private int $likeCount,
        private int $rating,
        private int $status,
        private ?int $replyTo,
        private array $replies,
        \DateTime $createdAt,
        \DateTime $updatedAt
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    public function getReplies(): array
    {
        return $this->replies;
    }


    /**
     * @return int|null
     */
    public function getReplyTo(): ?int
    {
        return $this->replyTo;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    public function getEntity()
    {
        return $this->entity;
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
    public function getVisitor(): Visitor
    {
        return $this->visitor;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return int
     */
    public function getLikeCount(): int
    {
        return $this->likeCount;
    }

    /**
     * @return int
     */
    public function getRating(): int
    {
        return $this->rating;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    public static function getHrStatus($status)
    {
        return static::getHrStatuses()[$status];
    }

    /**
     * @return array
     */
    public static function getHrStatuses(): array
    {
        return array(
            self::STATUS_NEW => 'New',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_UNPUBLISHED => 'Unpublished',
        );
    }
}