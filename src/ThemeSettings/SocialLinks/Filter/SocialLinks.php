<?php

namespace Skeletor\ThemeSettings\SocialLinks\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class SocialLinks implements FilterInterface
{
    public function __construct(protected \Skeletor\ThemeSettings\SocialLinks\Validator\SocialLinks $validator)
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
            'platform' => $data['platform'],
            'url' => $data['url'],
            'position' => $data['position'] ?? 1
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);
        return $data;
    }
}