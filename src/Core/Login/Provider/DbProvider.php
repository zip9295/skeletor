<?php
namespace Skeletor\Core\Login\Provider;

use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Repository\LoginRepositoryInterface;

class DbProvider implements ProviderInterface
{
    public function __construct(public readonly LoginRepositoryInterface $repository) {}

    public function login($data)
    {
        $model = $this->repository->findByEmail($data['email']);
        if (!password_verify($data['password'], $model->getPassword())) {
            throw new InvalidCredentials();
        }
        $this->repository->updateLoginInfo($model);

        return $model;
    }

    public function getByEmail($email)
    {
        return $this->repository->findByEmail($email);
    }

    public function updatePassword($userId, $password)
    {
        $this->repository->updatePassword($userId, $password);
    }
}