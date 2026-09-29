<?php
namespace Skeletor\User\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Factory\AbstractFactory;
use Skeletor\Tenant\Entity\Tenant;
use Skeletor\User\Model\Admin;
use Skeletor\User\Model\Staff;
use Skeletor\User\Model\User;
use Skeletor\User\Model\UserInterface;

class UserFactory extends AbstractFactory
{
    public static function compileEntityForUpdate($data, $entityManager)
    {
        $user = $entityManager->getRepository(\Skeletor\User\Entity\User::class)->find($data['id']);
        if (isset($data['tenantId'])) {
            $tenant = $entityManager->getRepository(Tenant::class)->find($data['tenantId']);
            unset($data['tenantId']);
        }
        if (isset($tenant)) {
            $user->setTenant($tenant);
        }
        $user->firstName = $data['firstName'];
        $user->lastName = $data['lastName'];
        $user->email = $data['email'];
        if(trim($data['password']) !== '') {
            $user->password = $data['password'];
        }
        $user->role = $data['role'];
        $user->isActive = $data['isActive'];
        $user->displayName = $data['displayName'];
        $entityManager->persist($user);

        return $user->getId();
    }

    public static function compileEntityForCreate($data, $entityManager)
    {
        $user = new \Skeletor\User\Entity\User();
        if (isset($data['tenantId'])) {
            $tenant = $entityManager->getRepository(Tenant::class)->find($data['tenantId']);
            unset($data['tenantId']);
        }
        if (isset($tenant)) {
            $user->setTenant($tenant);
        }
        $user->firstName = $data['firstName'];
        $user->lastName = $data['lastName'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->role = $data['role'];
        $user->isActive = $data['isActive'];
        $user->displayName = $data['displayName'];
        $entityManager->persist($user);
        $entityManager->flush();

        return $user->getId();
    }

}