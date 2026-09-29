<?php
namespace Skeletor\Core\Validator;


use Skeletor\User\Repository\UserRepository;
use Skeletor\User\Validator\Login;
use Skeletor\User\Validator\User;
use Skeletor\Core\Security\Csrf;

class ValidatorFactory
{
    private $validator;

    public function __construct(UserRepository $userRepo, Csrf $csrf)
    {

    }


    public function make($validatorClass)
    {
        switch ($validatorClass) {
            case Login::class:

                break;

            case User::class:

                break;
        }

    }
}