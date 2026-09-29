<?php
namespace Skeletor\Visitor\Model;

use Skeletor\Blog\Model\Post;
use Skeletor\Core\Acl\AclInterface;
use Skeletor\Core\Model\Model;
use Skeletor\Image\Model\Image;

class Visitor extends Model implements AclInterface, VisitorInterface
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * @param int $id
     * @param string $password
     * @param string $email
     * @param int $role
     * @param int $isActive
     * @param string|null $ipv4
     * @param \DateTime|null $lastLogin
     * @param string|null $firstName
     * @param string|null $lastName
     * @param \DateTime $createdAt
     * @param \DateTime $updatedAt
     * @param Image|null $avatar
     * @param string|null $displayName
     */
    public function __construct(
        private string $id, private string $password, private string $email, private int $role, private int $isActive,
        private ?string $firstName, private ?string $lastName, private ?string $ipv4 = null, private ?\DateTime $lastLogin = null,
        private ?\DateTime $createdAt = null, private ?\DateTime $updatedAt = null
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return mixed
     */
    public function getIpv4()
    {
        return long2ip((int) $this->ipv4);
    }

    /**
     * @return mixed
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public static function getHrRole($type)
    {
        return static::getHrRoles()[$type];
    }

    /**
     * @return array
     */
    public static function getHrRoles(): array
    {
        return array(
            self::ROLE_STANDARD => 'Standard',
//            self::ROLE_LEVEL1 => 'Level1',
        );
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return int
     */
    public function getRole(): int
    {
        return (int) $this->role;
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return (bool) $this->isActive;
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
    public function getId(): string
    {
        return $this->id;
    }
}