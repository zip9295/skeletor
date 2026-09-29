<?php
namespace Skeletor\User\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements AuthenticatableInterface
{
    use \Skeletor\Core\Entity\Timestampable;

    const ROLE_GUEST = 0;
    const ROLE_ADMIN = 1;
    const ROLE_STAFF = 5;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    public string $firstName;
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    public string $lastName;
    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    public string $email;
    #[ORM\Column(type: Types::STRING, length: 128)]
    public string $password;
    #[ORM\Column(type: Types::SMALLINT, length: 1)]
    public string $role;
    #[ORM\Column(type: Types::INTEGER)]
    public string $isActive;
    #[ORM\Column(type: Types::STRING, length: 128)]
    public string $displayName;
    #[ORM\Column(type: Types::INTEGER, nullable: true, options:["unsigned"=>true])]
    public ?string $ipv4;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $lastLogin;

    /**
     * Where a user lands after logging in.
     *
     * Not "/": this application routes "/" to the login form, so sending a freshly
     * authenticated user there bounced them straight back into it. Matches the redirectUri
     * in config, which is the same intent expressed for the guest case.
     */
    protected $redirectPath = '/user/view/';

//    #[ORM\ManyToOne(targetEntity: Tenant::class, fetch: 'EAGER')]
//    #[ORM\JoinColumn(name: 'tenantId', referencedColumnName: 'id')]
//    private Tenant $tenant;

//    public function setTenant(Tenant $tenant)
//    {
//        $this->tenant = $tenant;
//    }
//
//    public function getTenant()
//    {
//        return $this->tenant;
//    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function updateLoginInfo($ipv4, $lastLogin): void
    {
        $this->ipv4 = $ipv4;
        $this->lastLogin = $lastLogin;
    }

    public function getAuthIdentifier(): string
    {
        return $this->email;
    }

    public function getAuthPassword(): ?string
    {
        return $this->password;
    }

    public function getAuthRole(): int
    {
        return (int) $this->role;
    }

    public function isActive(): bool
    {
        return (bool) $this->isActive;
    }

    public function supportsAuthenticator(string $authenticatorType): bool
    {
        return in_array($authenticatorType, ['password', 'magic_link']);
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

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

    public function getRedirectPath(): string
    {
        return $this->redirectPath;
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
            self::ROLE_ADMIN => 'Admin',
//            self::ROLE_EDITOR => 'Staff',
//            self::ROLE_JOURNALIST => 'Staff',
//            self::ROLE_AUTHOR => 'Staff',
            self::ROLE_STAFF => 'Staff',
        );
    }

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
     * @return string
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}