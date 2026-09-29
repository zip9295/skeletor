<?php
namespace Skeletor\Lead\Model;

use Skeletor\Core\Model\Model;

class Lead extends Model
{
    public function __construct(
        private string $id, private string $email, private ?string $firstName = null, private ?string $lastName = null,
        private ?string $phoneNumber = null, private ?int $status = null, private ?string $source = null,
        $createdAt = null, $updatedAt = null,
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }


}