<?php
namespace Skeletor\Address\Filter;

use Skeletor\Core\Filter\Str;
use Skeletor\Address\Validator\Address as Validator;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;

class Address implements FilterInterface
{
    /**
     * @var Validator
     */
    private $validator;

    public function __construct(Validator $validator)
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
        $data = [
            'addressId' => (isset($postData['addressId'])) ? (int) ($postData['addressId']) : null,
            'address' => $postData['address'],
            'streetNumber' => $postData['streetNumber'],
            'floor' => $postData['floor'],
            'appNumber' => $postData['appNumber'],
            'city' => $postData['city'],
            'zip' => $postData['zip'],
            'phone' => $alnum($postData['phone']),
            'name' => isset($postData['name']) ? $alnum($postData['name']) : '',
            'note' => isset($postData['note']) ? $alnum($postData['note']) : '',
            'cityId' => (int) ($postData['cityId']),
            'countryId' => (int) ($postData['countryId']),
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }

        return $data;
    }

}