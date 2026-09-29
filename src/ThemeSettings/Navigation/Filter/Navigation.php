<?php

namespace Skeletor\ThemeSettings\Navigation\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class Navigation implements FilterInterface
{
    public function __construct(protected \Skeletor\ThemeSettings\Navigation\Validator\Navigation $validator)
    {
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $data): array
    {
        $data = [
            'id' => (isset($data['id'])) ? $data['id'] : null,
            'label' => $data['label'],
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);
        return $data;
    }
}