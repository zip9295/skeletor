<?php

namespace Skeletor\Subscription\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Subscription\Model\Subscription as DtoModel;

#[ORM\Entity]
#[ORM\Table(name: 'subscription')]
class Subscription
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(type: Types::INTEGER)]
    private int $price;

    #[ORM\Column(type: Types::INTEGER)]
    private int $isActive;

    #[ORM\Column(type: Types::INTEGER)]
    private int $paymentPeriod;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $remoteId;

    public function populateFromDto(DtoModel $dto)
    {
        if ($dto->getId()) {
            $this->id = $dto->getId();
        }
        $this->title = $dto->getTitle();
        $this->description = $dto->getDescription();
        $this->price = $dto->getPrice();
        $this->isActive = $dto->getIsActive();
        $this->paymentPeriod = $dto->getPaymentPeriod();
        $this->remoteId = $dto->getRemoteId();

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }
}