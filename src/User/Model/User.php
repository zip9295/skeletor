<?php
namespace Skeletor\User\Model;

use Skeletor\Core\Acl\AclInterface;
use Skeletor\Core\Model\Model;


/**
 * Class User.
 * Base user model.
 *
 * @package SNF\User\Model
 */
class User extends Model implements UserInterface
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    private $id;

    private $password;

    private $email;

    private $displayName;

    private $role;

    private $isActive;

    private $ipv4;

    private $lastLogin;

    private $firstName;

    private $lastName;

    /**
     * Redirect path after login/
     * @TODO move to config or other storage
     *
     * @var string
     */
    protected $redirectPath = '/admin/user/view/';

    public function __construct(
        $id, $password, $email, $role, $isActive, $displayName, $firstName, $lastName, $ipv4 = null, $lastLogin = null, $createdAt = null, $updatedAt = null
    ) {
        parent::__construct($createdAt, $updatedAt);
        $this->id = $id;
        $this->password = $password;
        $this->email = $email;
        $this->role = $role;
        $this->isActive = $isActive;
        $this->displayName = $displayName;
        $this->ipv4 = $ipv4;
        $this->lastLogin = $lastLogin;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
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

    public function getRedirectPath()
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
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return int
     */
    public function getId(): string
    {
        return $this->id;
    }
}
