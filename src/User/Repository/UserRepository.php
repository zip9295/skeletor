<?php
namespace Skeletor\User\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Core\Login\Repository\LoginRepositoryInterface;
use Skeletor\Tenant\Model\Tenant;
use Skeletor\User\Model\UserInterface as User;

/**
 * Class UserRepository.
 *
 */
class UserRepository extends TableViewRepository implements LoginRepositoryInterface, UserRepositoryInterface
{
    const FACTORY = \Skeletor\User\Factory\UserFactory::class;
    const ENTITY = \Skeletor\User\Entity\User::class;

    public function __construct(EntityManagerInterface $em, private \DateTime $dt)
    {
        parent::__construct($em);
    }

    /**
     * @param $email
     *
     * @return User
     * @throws \Exception
     */
    public function findByEmail(string $email)
    {
        $userEntity = $this->entityManager->getRepository(static::ENTITY)->findBy(['email' => $email]);
        if (!isset($userEntity[0])) {
            throw new NotFoundException();
        }
        return $userEntity[0];
    }

    public function getByTenant(Tenant $tenant)
    {
        return $this->entityManager->getRepository(static::ENTITY)->findBy(['tenant' => $tenant->getId()]);
    }

    public function emailExists($email): bool
    {
        try {
            $this->findByEmail($email);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function getSearchableColumns(): array
    {
        return ['a.email', 'a.firstName', 'a.lastName'];
    }

    public function updateLoginInfo($model)
    {
        $entity = $this->entityManager->getRepository(static::ENTITY)->find($model->getId());
        $entity->ipv4 = ip2long($_SERVER['REMOTE_ADDR']);
        $entity->lastLogin = $this->dt;
        $this->entityManager->flush();
    }

    public function updatePassword($userId, $password)
    {
        $user = $this->entityManager->getRepository(static::ENTITY)->find($userId);
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $this->entityManager->flush();
    }

//    public function getById(int $id)
//    {
//        return $this->entityManager->getRepository(static::ENTITY)->find($id);
//    }
}