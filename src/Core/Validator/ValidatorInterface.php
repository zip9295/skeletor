<?php
namespace Skeletor\Core\Validator;


interface ValidatorInterface
{
    public function isValid(array $data): bool;

    public function getMessages(): array;
}