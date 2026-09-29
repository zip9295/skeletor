<?php

namespace Skeletor\Lead\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class Lead implements FilterInterface
{
    public function __construct(private \Skeletor\Lead\Validator\Lead $validator)
    {
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData) : array
    {
        $phoneNumber = null;
        if(isset($postData['phoneNumber']) && trim($postData['phoneNumber']) !== '') {
            $phoneNumber = $postData['phoneNumber'];
        }
        $firstName = null;
        if(isset($postData['firstName']) && trim($postData['firstName']) !== '') {
            $firstName = $postData['firstName'];
        }
        $lastName = null;
        if(isset($postData['lastName']) && trim($postData['lastName']) !== '') {
            $lastName = $postData['lastName'];
        }
        $source = null;
        if(isset($postData['source']) && trim($postData['source']) !== '') {
            $source = $postData['source'];
        }

        $status = null;
        if(isset($postData['status']) && trim($postData['status']) !== '') {
            $status = $postData['status'];
        }
        $data = [
            'id' => (isset($postData['id'])) ? $postData['id'] : null,
            'email' => $postData['email'],
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phoneNumber' => $phoneNumber,
            'status' => $status,
            'source' => $source,
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if (!$this->validator->isValid($data)) {
            $e = new ValidatorException();
            $e->data = $this->validator->getMessages();
            throw $e;
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }
}