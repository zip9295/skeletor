<?php

namespace Skeletor\Visitor\Repository;

use League\Event\EventDispatcher;
use Skeletor\Core\Model\Model;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Core\Login\Repository\LoginRepositoryInterface;
use Skeletor\User\Model\UserInterface as User;
use Skeletor\Visitor\Model\VisitorInterface as Visitor;
use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Mapper\NotFoundException;

/**
 * Class UserRepository.
 *
 */
class VisitorRepository extends TableViewRepository implements VisitorRepositoryInterface, LoginRepositoryInterface
{
    const FACTORY = \Skeletor\Visitor\Factory\VisitorFactory::class;
    const ENTITY = \Skeletor\Visitor\Entity\Visitor::class;

    public function __construct(EntityManagerInterface $em, private \DateTime $dt)
    {
        parent::__construct($em);
    }

    /**
     * @param string $email
     * @return Visitor
     */
    public function findByEmail(string $email): Visitor
    {
        $visitorEntity = $this->entityManager->getRepository(static::ENTITY)->findBy(['email' => $email]);
        if (!isset($visitorEntity[0])) {
            throw new NotFoundException();
        }
        return static::FACTORY::make($this->entityManager->getUnitOfWork()->getOriginalEntityData($visitorEntity[0]), $this->entityManager);
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
        return ['email', 'firstName', 'lastName'];
    }

    public function updateLoginInfo($user)
    {
        $entity = $this->entityManager->getRepository(static::ENTITY)->find($user->getId());
        $entity->updateLoginInfo(ip2long($_SERVER['REMOTE_ADDR']), $this->dt);
        $this->entityManager->flush();
    }

    public function updatePassword($id, $password)
    {
        $user = $this->entityManager->getRepository(static::ENTITY)->find($id);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
        $this->entityManager->flush();
    }
}

