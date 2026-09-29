<?php
namespace Skeletor\Core\Login\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Login\Validator\ForgotPassword as ForgotPasswordValidator;
use Skeletor\Core\Security\Csrf;

class ForgotPassword implements FilterInterface
{

    public function __construct(private ForgotPasswordValidator $validator)
    {
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter($postData) : array
    {
        $data = [
            'email' => trim((string) ($postData['email'] ?? '')),
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME] ?? '',
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }

}