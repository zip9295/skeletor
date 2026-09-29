<?php
namespace Skeletor\Visitor\Filter;

use Psr\Http\Message\ServerRequestInterface as Request;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Visitor\Validator\Login as LoginValidator;
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
        $data = [
            'password' => $postData['password'],
            'email' => $postData['email'],
            'rememberMe' => (isset($postData['rememberMe'])) ? 1:0,
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }

}