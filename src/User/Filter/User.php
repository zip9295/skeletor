<?php
namespace Skeletor\User\Filter;

use Skeletor\Core\Filter\Str;
use Skeletor\ContentEditor\Contracts\ContentEditorFilterInterface;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\User\Validator\User as UserValidator;
use Skeletor\Core\Security\Csrf;

class User implements FilterInterface
{

    protected $validator;

    public function __construct(UserValidator $validator)
    {
        $this->validator = $validator;
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData) : array
    {
        $alnum = static fn ($v) => Str::alnum((string) $v, true);
        $data = [
            'id' => (isset($postData['id'])) ? $postData['id'] : null,
            'password' => (isset($postData['password'])) ? $postData['password'] : null,
            'password2' => (isset($postData['password2'])) ? $postData['password2'] : null,
            'email' => $postData['email'],
            'role' => $postData['role'],
            'isActive' => (int) ($postData['isActive']),
            'displayName' => (strlen($alnum($postData['displayName'])) > 0) ? $alnum($postData['displayName']) :
                $alnum($postData['firstName'] .' '. $postData['lastName']),
            'firstName' => $alnum($postData['firstName']),
            'lastName' => $alnum($postData['lastName']),
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        if(trim($data['password']) !== '' && trim($data['password2']) !== '') {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        unset($data[Csrf::TOKEN_NAME]);
        unset($data['password2']);

        return $data;
    }

}