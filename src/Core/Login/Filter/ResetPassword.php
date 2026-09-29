<?php
namespace Skeletor\Core\Login\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Login\Validator\ResetPasswordInterface as ResetPasswordValidator;
use Skeletor\Core\Security\Csrf;

class ResetPassword implements FilterInterface
{

    public function __construct(private ResetPasswordValidator $validator)
    {
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter($postData) : array
    {
        $data = [
            'password' => (string) ($postData['password'] ?? ''),
            'password2' => (string) ($postData['password2'] ?? ''),
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME] ?? '',
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }

}