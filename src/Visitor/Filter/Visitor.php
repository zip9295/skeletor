<?php
namespace Skeletor\Visitor\Filter;

use Skeletor\Core\Filter\Str;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Visitor\Validator\Visitor as VisitorValidator;
use Skeletor\Core\Security\Csrf;

class Visitor implements FilterInterface
{
    protected $validator;

    public function __construct(VisitorValidator $validator)
    {
        $this->validator = $validator;
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData) : array
    {
        if (strlen(trim($postData['submitKey']))) {
            die('watching you.');
        }
        $alnum = static fn ($v) => Str::alnum((string) $v, true);
        $data = [
            'id' => (isset($postData['id'])) ? $postData['id'] : null,
            'currentPassword' => (isset($postData['currentPassword'])) ? $postData['currentPassword'] : null,
            'password' => (isset($postData['password'])) ? $postData['password'] : null,
            'password2' => (isset($postData['password2'])) ? $postData['password2'] : null,
            'email' => $postData['email'],
            'subscriptions' => $postData['subscriptions'] ?? [],
            'role' => isset($postData['role']) ? $postData['role']:1,
            'isActive' => (int) ($postData['isActive']),
            'firstName' => $postData['firstName'],
            'lastName' => $postData['lastName'],
            'displayName' => $alnum($postData['displayName'] ?? ''),
            'avatar' => $postData['image'] ?? '',
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if(isset($postData['registerForm']) && $postData['registerForm']) {
            $data['registerForm'] = true;
        }
        if (!$this->validator->isValid($data)) {
            $e = new ValidatorException();
            $e->data = $this->validator->getMessages();
            throw $e;
        }
        unset($data[Csrf::TOKEN_NAME]);
        unset($data['password2']);
        unset($data['currentPassword']);
        unset($data['registerForm']);

        return $data;
    }

}