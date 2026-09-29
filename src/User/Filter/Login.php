<?php

namespace Skeletor\User\Filter;

use Skeletor\Core\Filter\Str;
use Psr\Http\Message\ServerRequestInterface as Request;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\User\Validator\Login as LoginValidator;
use Skeletor\Core\Security\Csrf;

class Login implements FilterInterface
{
    /**
     * @var LoginValidator
     */
    private $validator;

    public function __construct(LoginValidator $validator)
    {
        $this->validator = $validator;
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData): array
    {
        $alnum = static fn ($v) => Str::alnum((string) $v, true);
        // Defaulted rather than read directly: a request that omits a field is a failed
        // login, not an undefined-index warning on the way to one.
        $data = [
            'password' => (string) ($postData['password'] ?? ''),
            'email' => trim((string) ($postData['email'] ?? '')),
            'rememberMe' => (isset($postData['rememberMe'])) ? 1:0,
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME] ?? '',
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }

}