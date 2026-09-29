<?php

namespace Skeletor\Subscription\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Service\BlocksParser;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Subscription\Validator\Subscription as Validator;
use Skeletor\Core\Security\Csrf;

class Subscription implements FilterInterface
{
    public function __construct(private Validator $validator, private BlocksParser $blocksParser) {}

    public function getErrors(): array
    {
        return $this->validator->getMessages();
    }

    /**
     * @throws ValidatorException
     */
    public function filter(array $postData): array
    {
        $description = '';
        if (isset($postData['blocks'])) {
            $description = json_encode($this->blocksParser->parse((array) $postData['blocks']));
        }
        $data = [
            'id' => $postData['id'] ?? null,
            'title' => $postData['title'],
            'paymentPeriod' => $postData['paymentPeriod'],
            'price' => $postData['price'] * 1000, // convert decimal
            'isActive' => $postData['isActive'],
            'remoteId' => $postData['remoteId'] ?? null,
            'description' => $description,
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);
        return $data;
    }
}