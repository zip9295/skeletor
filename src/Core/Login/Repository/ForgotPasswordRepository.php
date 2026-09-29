<?php
namespace Skeletor\Core\Login\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Core\Login\Model\ForgotPasswordToken as Model;

class ForgotPasswordRepository extends TableViewRepository
{
    const ENTITY = \Skeletor\Core\Login\Entity\ForgotPasswordToken::class;
    const FACTORY = \Skeletor\Core\Login\Factory\ForgotPasswordTokenFactory::class;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em);
    }

    public function getSearchableColumns(): array
    {
        return ['token'];
    }

    /**
     * Updates user model.
     *
     * @param $data
     * @return Model
     * @throws \Exception
     *
     */
    public function update($data): Model
    {
        throw new \Exception('Cannot be used.');
    }

    public function resetToken($id)
    {
        $token = $this->entityManager->getRepository(static::ENTITY)->find($id);
        $token->resetToken();
        $this->entityManager->flush();
    }
}