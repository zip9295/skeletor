<?php

namespace Skeletor\File\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class File implements FilterInterface
{
    public function __construct(private \Skeletor\File\Validator\File $validator)
    {
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData): array
    {
        $data = [
            'id' => $postData['id'],
//            'name' => $postData['name'],
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);
        return $data;
    }
}