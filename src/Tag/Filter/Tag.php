<?php

namespace Skeletor\Tag\Filter;

use Skeletor\Tag\Validator\Tag as Validator;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class Tag implements FilterInterface
{
    public function __construct(protected Validator $validator)
    { }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData): array
    {
        $data = [
            'id' => $postData['id'],
            'priceLabel' => $postData['priceLabel'],
            'stickerLabel' => $postData['stickerLabel'],
            'stickerImagePosition' => $postData['stickerImagePosition'],
            'title' => $postData['title'],
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if (isset($postData['stickerImageId'])) {
            $data['stickerImageId'] = $postData['stickerImageId'];
        }
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }
}