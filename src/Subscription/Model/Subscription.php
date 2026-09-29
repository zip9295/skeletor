<?php

namespace Skeletor\Subscription\Model;

use Skeletor\Core\Behaviors\Arrayable;
use Skeletor\Core\Model\Model;

class Subscription extends Model
{
    const PERIOD_MONTHLY = 1;
    const PERIOD_YEARLY = 2;
    const PERIOD_ONE_TIME = 3;

    use Arrayable;

    public function __construct(
        private string $title,
        private ?float $price,
        private ?int $isActive,
        private ?string $id,
        private int $paymentPeriod,
        private ?string $remoteId,
        private string $description,
        private array $blocks = [],
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null)
    {
        parent::__construct($createdAt, $updatedAt);
    }

    /**
     * @return string|null
     */
    public function getRemoteId(): ?string
    {
        return $this->remoteId;
    }

    /**
     * @return int|null
     */
    public function getIsActive(): ?int
    {
        return $this->isActive;
    }

    /**
     * @return int|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    public function getName()
    {
        return $this->getTitle();
    }

    /**
     * @return int|null
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getPaymentPeriod()
    {
        return $this->paymentPeriod;
    }

    public static function getHrPeriod($type)
    {
        return static::getHrPeriods()[$type];
    }

    /**
     * @return array
     */
    public static function getHrPeriods(): array
    {
        return array(
            self::PERIOD_MONTHLY => 'Monthly',
            self::PERIOD_YEARLY => 'Yearly',
            self::PERIOD_ONE_TIME => 'One time',
        );
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }
}